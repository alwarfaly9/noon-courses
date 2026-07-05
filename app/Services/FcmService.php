<?php

namespace App\Services;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class FcmService
{
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
     * Requires a service account JSON key file (configured via FCM_SERVICE_ACCOUNT_PATH env).
     */
    private static function sendToToken(string $token, string $title, string $body, array $data = []): void
    {
        $projectId = config('services.fcm.project_id');
        $serviceAccountPath = config('services.fcm.service_account_path');

        if (empty($projectId) || empty($serviceAccountPath)) {
            Log::warning('[FCM] Firebase project_id or service_account_path not configured.');
            return;
        }

        try {
            $accessToken = self::getAccessToken($serviceAccountPath);

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
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

            if ($response->status() === 404 || ($response->status() === 400 && str_contains($response->body(), 'UNREGISTERED'))) {
                DeviceToken::where('token', $token)->update(['is_active' => false]);
            }

            if ($response->failed()) {
                Log::error('[FCM] Push notification failed: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('[FCM] Failed to send push notification: ' . $e->getMessage());
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

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if ($response->failed()) {
                throw new \RuntimeException('[FCM] Failed to obtain access token: ' . $response->body());
            }

            return $response->json('access_token');
        });
    }
}
