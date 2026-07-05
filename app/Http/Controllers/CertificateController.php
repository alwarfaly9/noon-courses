<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\CourseEnrollment;
use App\Models\Course;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class CertificateController extends Controller
{
    // Student: Get or Generate Certificate
    public function getCertificate(Request $request, $courseId)
    {
        $user = $request->user();
        
        // Check enrollment and progress
        $enrollment = CourseEnrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$enrollment) {
            return response()->json(['success' => false, 'message' => 'Not enrolled'], 403);
        }

        if ($enrollment->progress_percentage < 100) {
            return response()->json([
                'success' => false, 
                'message' => 'Course not completed yet. Current progress: ' . $enrollment->progress_percentage . '%'
            ], 400);
        }

        // Check if certificate already exists
        $certificate = Certificate::where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->first();

        if (!$certificate) {
            // Generate new certificate record
            $certificate = Certificate::create([
                'certificate_id' => 'CERT-' . strtoupper(Str::random(10)),
                'user_id' => $user->id,
                'course_id' => $courseId,
                'issued_at' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $certificate,
            'download_url' => url("/api/certificates/{$certificate->certificate_id}/download")
        ]);
    }

    // Public: Download Certificate PDF
    public function download($certificateId)
    {
        $certificate = Certificate::with(['user', 'course.teacher'])
            ->where('certificate_id', $certificateId)
            ->firstOrFail();

        $platformName = Setting::get('platform_name', 'EdLibya');
        $platformLogoUrl = Setting::get('platform_logo_url');

        // Convert local storage file to base64 so DomPDF renders it without HTTP
        $platformLogo = null;
        if ($platformLogoUrl) {
            $urlPath = ltrim(parse_url($platformLogoUrl, PHP_URL_PATH), '/');
            $absolutePath = public_path($urlPath);
            if (file_exists($absolutePath)) {
                $mime = mime_content_type($absolutePath);
                $b64  = base64_encode(file_get_contents($absolutePath));
                $platformLogo = "data:{$mime};base64,{$b64}";
            } else {
                $platformLogo = $platformLogoUrl;
            }
        }

        $data = [
            'certificate'  => $certificate,
            'user'         => $certificate->user,
            'course'       => $certificate->course,
            'date'         => $certificate->issued_at->format('F j, Y'),
            'platformName' => $platformName,
            'platformLogo' => $platformLogo,
        ];

        $pdf = Pdf::loadView('certificates.template', $data)
            ->setPaper('a4', 'landscape');

        return $pdf->download("certificate-{$certificateId}.pdf");
    }

    // Public: Verify Certificate
    public function verify($certificateId)
    {
        $certificate = Certificate::with(['user', 'course'])->where('certificate_id', $certificateId)->first();

        if (!$certificate) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Certificate ID'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Certificate is valid',
            'data' => [
                'student' => $certificate->user->name,
                'course' => $certificate->course->title,
                'issued_at' => $certificate->issued_at->toIso8601String(),
            ]
        ]);
    }
}
