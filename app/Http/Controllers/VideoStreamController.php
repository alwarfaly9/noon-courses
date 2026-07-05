<?php

namespace App\Http\Controllers;

use App\Models\CourseLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * VideoStreamController
 *
 * Provides HTTP byte-range-aware video streaming for private lesson files.
 * Authentication is handled via an encrypted, time-limited token that is
 * issued by CourseController::getVideoUrl() and embedded in the URL as a
 * query parameter — allowing native video players (AVPlayer / ExoPlayer)
 * to fetch the stream without custom Authorization headers.
 *
 * Flow:
 *   1. Student authenticates → POST /auth/login → Bearer token
 *   2. Student requests     → GET /student/lessons/{id}/video-url (Bearer)
 *      → returns { data: { video_url: "…/video/stream/{id}?token=…" } }
 *   3. video_player opens the streaming URL (no custom headers needed)
 *   4. This controller validates the Crypt token and streams bytes
 *      with full Range-request support (RFC 7233).
 */
class VideoStreamController extends Controller
{
    private const BUFFER_SIZE = 65536; // 64 KB – optimal for streaming

    /**
     * Stream a lesson video.
     *
     * Supports:
     *  - Full file (200)
     *  - Single range (206) e.g. "bytes=0-1048575"
     *  - Open-ended range (206) e.g. "bytes=1048576-"
     */
    public function stream(Request $request, int $lessonId): mixed
    {
        // ── 1. Validate token ───────────────────────────────────────────────
        $token = $request->query('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Missing stream token',
            ], 401);
        }

        try {
            $payload = json_decode(
                Crypt::decryptString($token),
                true,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (\Throwable) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid stream token',
            ], 403);
        }

        // ── 2. Check expiry ─────────────────────────────────────────────────
        if (empty($payload['expires']) || now()->timestamp > (int) $payload['expires']) {
            return response()->json([
                'success' => false,
                'message' => 'Stream token has expired',
            ], 403);
        }

        // ── 3. Lesson ID must match token payload ───────────────────────────
        if ((int) ($payload['lesson_id'] ?? 0) !== $lessonId) {
            return response()->json([
                'success' => false,
                'message' => 'Token / lesson mismatch',
            ], 403);
        }

        // ── 4. Resolve file path ────────────────────────────────────────────
        /** @var CourseLesson $lesson */
        $lesson = CourseLesson::findOrFail($lessonId);
        $storedPath = $lesson->content_url ?: $lesson->content_file;

        if (!$storedPath) {
            return response()->json([
                'success' => false,
                'message' => 'No video attached to this lesson',
            ], 404);
        }

        // External URL (e.g. YouTube embed URL stored by teacher) – redirect
        if (str_starts_with($storedPath, 'http://') || str_starts_with($storedPath, 'https://')) {
            return redirect()->away($storedPath);
        }

        // ── 5. Verify file exists on the private disk ───────────────────────
        if (!Storage::disk('private')->exists($storedPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Video file not found',
            ], 404);
        }

        $filePath = storage_path('app/private/' . ltrim($storedPath, '/'));

        // ── 6. Handle OPTIONS pre-flight (for web) ──────────────────────────
        if ($request->isMethod('OPTIONS')) {
            return response('', 204, $this->corsHeaders());
        }

        return $this->buildStreamResponse($request, $filePath);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Build a 200 or 206 streaming response with correct RFC-7233 headers.
     */
    private function buildStreamResponse(Request $request, string $filePath): StreamedResponse
    {
        $fileSize  = (int) filesize($filePath);
        $mimeType  = $this->detectMimeType($filePath);
        $rangeHeader = $request->header('Range');

        if ($rangeHeader && preg_match('/bytes=(\d+)-(\d*)/', $rangeHeader, $m)) {
            // ── Partial Content (206) ───────────────────────────────────────
            $start = (int) $m[1];
            $end   = ($m[2] !== '') ? (int) $m[2] : $fileSize - 1;
            $end   = min($end, $fileSize - 1);

            if ($start > $end || $start >= $fileSize) {
                // 416 Range Not Satisfiable
                return new StreamedResponse(
                    fn () => null,
                    416,
                    array_merge($this->corsHeaders(), [
                        'Content-Range' => "bytes */{$fileSize}",
                    ])
                );
            }

            $chunkSize = $end - $start + 1;

            return new StreamedResponse(
                function () use ($filePath, $start, $chunkSize) {
                    $handle    = fopen($filePath, 'rb');
                    fseek($handle, $start);
                    $remaining = $chunkSize;
                    while (!feof($handle) && $remaining > 0) {
                        $read       = min(self::BUFFER_SIZE, $remaining);
                        $data       = fread($handle, $read);
                        $remaining -= strlen($data);
                        echo $data;
                        if (ob_get_level()) ob_flush();
                        flush();
                    }
                    fclose($handle);
                },
                206,
                array_merge($this->corsHeaders(), [
                    'Content-Type'        => $mimeType,
                    'Content-Length'      => $chunkSize,
                    'Content-Range'       => "bytes {$start}-{$end}/{$fileSize}",
                    'Accept-Ranges'       => 'bytes',
                    'Content-Disposition' => 'inline',
                    'Cache-Control'       => 'no-cache, no-store',
                ])
            );
        }

        // ── Full file (200) ───────────────────────────────────────────────
        return new StreamedResponse(
            function () use ($filePath) {
                $handle = fopen($filePath, 'rb');
                while (!feof($handle)) {
                    echo fread($handle, self::BUFFER_SIZE);
                    if (ob_get_level()) ob_flush();
                    flush();
                }
                fclose($handle);
            },
            200,
            array_merge($this->corsHeaders(), [
                'Content-Type'        => $mimeType,
                'Content-Length'      => $fileSize,
                'Accept-Ranges'       => 'bytes',
                'Content-Disposition' => 'inline',
                'Cache-Control'       => 'no-cache, no-store',
            ])
        );
    }

    /** Detect MIME type from file extension (fast, no finfo overhead). */
    private function detectMimeType(string $filePath): string
    {
        return match (strtolower(pathinfo($filePath, PATHINFO_EXTENSION))) {
            'mp4'  => 'video/mp4',
            'webm' => 'video/webm',
            'ogg'  => 'video/ogg',
            'mov'  => 'video/quicktime',
            'mkv'  => 'video/x-matroska',
            'avi'  => 'video/x-msvideo',
            default => 'application/octet-stream',
        };
    }

    /** CORS headers required for in-app video players and web. */
    private function corsHeaders(): array
    {
        return [
            'Access-Control-Allow-Origin'  => config('app.url'),
            'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
            'Access-Control-Allow-Headers' => 'Range, Content-Type, Authorization',
            'Access-Control-Expose-Headers'=> 'Content-Range, Content-Length, Accept-Ranges',
        ];
    }
}
