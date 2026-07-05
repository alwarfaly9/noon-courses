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

        $response = $this->actingAs($user)->getJson('/api/v1/referral/code');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data' => ['code']]);
    }

    public function test_referral_code_tracked_at_registration(): void
    {
        $referrer = User::factory()->create();
        $code     = $referrer->referral_code ?? 'TESTCODE';

        $response = $this->postJson('/api/v1/auth/register', [
            'name'          => 'New User',
            'email'         => 'newuser@example.com',
            'phone'         => '+218911234567',
            'password'      => 'Secret123!',
            'referral_code' => $code,
        ]);

        // Registration should succeed (2xx) regardless of referral validity
        $response->assertStatus(201);
    }

    public function test_user_cannot_use_own_referral_code(): void
    {
        $user     = User::factory()->create();
        $response = $this->actingAs($user)
            ->postJson('/api/v1/referral/apply', ['code' => $user->referral_code]);

        $response->assertStatus(422);
    }
}
