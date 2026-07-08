<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use App\Models\NotificationAnalytics;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FcmService
{
    /** FCM error strings that indicate a token should be invalidated. */
    private const INVALID_TOKEN_ERRORS = [
        'INVALID_REGISTRATION',
        'INVALID_ARGUMENT',
        'TOKEN_NOT_REGISTERED',
        'NOT_FOUND',
        'UNREGISTERED',
    ];

    /**
     * Send a push notification to a specific user.
     */
    public static function sendToUser($user, string $title, string $body, array $data = []): void
    {
        $userId = $user instanceof User ? $user->id : $user;

        $tokens = DeviceToken::where('user_id', $userId)
            ->where('is_active', true)
            ->pluck('token')
            ->toArray();

        if (empty($tokens)) {
            return;
        }

        foreach ($tokens as $token) {
            self::sendToToken($token, $title, $body, $data);
        }
    }

    /**
     * Send a push notification to multiple users.
     */
    public static function sendToUsers(array $userIds, string $title, string $body, array $data = []): void
    {
        // Chunk to avoid massive queries if the array is very large
        collect($userIds)->chunk(500)->each(function ($chunkedUserIds) use ($title, $body, $data) {
            $tokens = DeviceToken::whereIn('user_id', $chunkedUserIds)
                ->where('is_active', true)
                ->pluck('token')
                ->toArray();

            foreach ($tokens as $token) {
                self::sendToToken($token, $title, $body, $data);
            }
        });
    }

    /**
     * Send push notification using Firebase HTTP v1 API.
     * Returns true on success, false on failure.
     */
    public static function sendToToken(string $token, string $title, string $body, array $data = []): bool
    {
        $projectId = config('services.fcm.project_id');
        $serviceAccountPath = config('services.fcm.service_account_path');

        if (empty($projectId) || empty($serviceAccountPath)) {
            Log::warning('[FCM] Firebase project_id or service_account_path not configured.');
            return false;
        }

        try {
            $accessToken = self::getAccessToken($serviceAccountPath);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $accessToken,
                    'Content-Type' => 'application/json',
                ])
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $token,
                        'notification' => [
                            'title' => $title,
                            'body' => $body,
                        ],
                        'data' => array_map('strval', $data),
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'sound' => 'default',
                            ],
                        ],
                        'apns' => [
                            'payload' => [
                                'aps' => [
                                    'sound' => 'default',
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                return true;
            }

            $bodyStr = $response->body();
            $status = $response->status();

            // Handle specific FCM error codes
            $invalidate = false;
            $reason = 'unknown';

            if ($status === 404) {
                $invalidate = true;
                $reason = 'NOT_FOUND';
            } elseif ($status === 400) {
                foreach (self::INVALID_TOKEN_ERRORS as $err) {
                    if (str_contains($bodyStr, $err)) {
                        $invalidate = true;
                        $reason = $err;
                        break;
                    }
                }
            } elseif ($status === 403) {
                $reason = 'AUTH_ERROR';
            } elseif ($status === 429) {
                $reason = 'RATE_LIMITED';
            }

            if ($invalidate) {
                DeviceToken::where('token', $token)->update(['is_active' => false]);
                Log::warning("[FCM] Token invalidated: {$reason}", ['token_prefix' => substr($token, 0, 20)]);
            }

            Log::error("[FCM] Push notification failed: status={$status} reason={$reason}", [
                'response' => substr($bodyStr, 0, 500),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('[FCM] Failed to send push notification: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get OAuth2 access token from service account credentials.
     * Caches the token until near expiry.
     */
    private static function getAccessToken(string $serviceAccountPath): string
    {
        return Cache::remember('fcm_access_token', 3500, function () use ($serviceAccountPath) {
            $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);

            $now = time();
            $header = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $payload = base64_encode(json_encode([
                'iss' => $serviceAccount['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            $unsignedJwt = $header . '.' . $payload;
            openssl_sign($unsignedJwt, $signature, $serviceAccount['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $unsignedJwt . '.' . rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('[FCM] Failed to obtain access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }

    /**
     * Delete all inactive device tokens older than N days.
     */
    public static function pruneInactiveTokens(int $days = 30): int
    {
        return DeviceToken::where('is_active', false)
            ->where('updated_at', '<=', now()->subDays($days))
            ->delete();
    }
}
