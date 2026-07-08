<?php

namespace App\Services;

use App\Mail\OtpMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    /**
     * Send OTP to phone or email (stores code in cache)
     */
    public function sendOtp(array $data)
    {
        if (empty($data['phone']) && empty($data['email'])) {
            throw new \InvalidArgumentException('Phone or email is required');
        }

        // Normalize email so cache keys are consistent
        if (!empty($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        $recipient = !empty($data['phone']) ? $data['phone'] : $data['email'];
        $type = !empty($data['phone']) ? 'phone' : 'email';

        // Generate cryptographically-secure 6-digit code
        $code = random_int(100000, 999999);

        // Cache for 5 minutes
        $key = "otp:{$type}:{$recipient}";
        Cache::put($key, (string) $code, now()->addMinutes(5));

        Log::info('[OTP] Code generated and cached', [
            'type'      => $type,
            'recipient' => $type === 'email' ? substr($recipient, 0, 4) . '***' : '***',
        ]);

        // Send OTP via email; for phone OTP integrate an SMS provider (e.g. Twilio) here.
        if ($type === 'email') {
            try {
                Mail::to($recipient)->queue(new OtpMail((string) $code));
            } catch (\Exception $e) {
                Log::error('[OTP] Failed to send OTP email to ' . $recipient . ': ' . $e->getMessage());
                throw new \RuntimeException('فشل إرسال البريد الإلكتروني، يرجى المحاولة لاحقاً');
            }
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(array $data)
    {
        if (empty($data['code']) || (empty($data['phone']) && empty($data['email']))) {
            throw new \InvalidArgumentException('Code and phone or email are required');
        }

        // Normalize email
        if (!empty($data['email'])) {
            $data['email'] = strtolower(trim($data['email']));
        }

        $recipient = !empty($data['phone']) ? $data['phone'] : $data['email'];
        $type = !empty($data['phone']) ? 'phone' : 'email';
        $key = "otp:{$type}:{$recipient}";

        $cached = Cache::get($key);
        if ($cached === null) {
            Log::info('[OTP] Verify attempt — code expired or not found', ['type' => $type]);
            throw new \RuntimeException('OTP expired or not found', 404);
        }

        // Brute-force guard: max 5 attempts per OTP key
        $attemptsKey = "otp_attempts:{$type}:{$recipient}";
        $attempts = (int) Cache::get($attemptsKey, 0);
        if ($attempts >= 5) {
            Cache::forget($key);
            Cache::forget($attemptsKey);
            Log::warning('[OTP] Too many failed attempts — OTP invalidated', ['type' => $type]);
            throw new \RuntimeException('Too many failed attempts. Please request a new OTP.', 429);
        }

        if ((string) $cached !== (string) $data['code']) {
            Cache::put($attemptsKey, $attempts + 1, now()->addMinutes(10));
            Log::warning('[OTP] Verify attempt — wrong code', ['type' => $type, 'attempt' => $attempts + 1]);
            throw new \RuntimeException('Invalid OTP', 400);
        }

        // Correct — consume OTP and clear attempt counter
        Cache::forget($key);
        Cache::forget($attemptsKey);

        // Store a short-lived "verified" flag so register() can confirm OTP
        if ($type === 'email') {
            $verifiedKey = "email_otp_verified:{$recipient}";
            Cache::put($verifiedKey, true, now()->addMinutes(15));
            Log::info('[OTP] Email verified — registration window opened', [
                'email' => substr($recipient, 0, 4) . '***',
            ]);
        }
    }
}
