<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_get_referral_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withToken($token)
            ->getJson('/api/v1/referrals');

        $response->assertStatus(200);
    }

    public function test_referral_code_tracked_at_registration(): void
    {
        $referrer = User::factory()->create();
        $referrer->assignRole('student');
        $code     = $referrer->referral_code ?? \Illuminate\Support\Str::random(8);
        $referrer->update(['referral_code' => $code]);

        \Illuminate\Support\Facades\Cache::put(
            "email_otp_verified:newuser@example.com", true, 600
        );

        $response = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'New User',
            'email'                 => 'newuser@example.com',
            'phone'                 => '+218911234567',
            'password'              => 'Secret123!',
            'password_confirmation' => 'Secret123!',
            'referral_code'         => $code,
        ]);

        $response->assertStatus(201);
    }

    public function test_user_cannot_use_own_referral_code(): void
    {
        $user     = User::factory()->create();
        $user->assignRole('student');
        $code     = $user->referral_code ?? \Illuminate\Support\Str::random(8);
        $user->update(['referral_code' => $code]);

        $result = app(\App\Services\ReferralService::class)
            ->trackRegistration($user, $code);

        $this->assertFalse($result);
    }
}
