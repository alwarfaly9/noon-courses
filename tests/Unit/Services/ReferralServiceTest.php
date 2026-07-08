<?php

namespace Tests\Unit\Services;

use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReferralServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReferralService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ReferralService::class);
    }

    public function test_get_or_create_code_returns_same_code(): void
    {
        $user = User::factory()->create();

        $code1 = $this->service->getOrCreateCode($user);
        $code2 = $this->service->getOrCreateCode($user);

        $this->assertEquals($code1, $code2);
    }

    public function test_code_is_8_characters(): void
    {
        $user = User::factory()->create();
        $code = $this->service->getOrCreateCode($user);

        $this->assertEquals(8, strlen($code));
    }

    public function test_track_registration_links_referrer(): void
    {
        $referrer = User::factory()->create();
        $newUser  = User::factory()->create();

        $code = $this->service->getOrCreateCode($referrer);
        $result = $this->service->trackRegistration($newUser, $code);

        $this->assertTrue($result);
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $referrer->id,
            'referred_id' => $newUser->id,
            'status'      => 'pending',
        ]);
    }

    public function test_self_referral_returns_false(): void
    {
        $user = User::factory()->create();
        $code = $this->service->getOrCreateCode($user);

        $result = $this->service->trackRegistration($user, $code);

        $this->assertFalse($result);
    }

    public function test_invalid_code_returns_false(): void
    {
        $newUser = User::factory()->create();

        $result = $this->service->trackRegistration($newUser, 'INVALID');

        $this->assertFalse($result);
    }

    public function test_duplicate_referral_returns_false(): void
    {
        $referrer = User::factory()->create();
        $newUser  = User::factory()->create();
        $code     = $this->service->getOrCreateCode($referrer);

        $this->service->trackRegistration($newUser, $code);
        $result = $this->service->trackRegistration($newUser, $code);

        $this->assertFalse($result);
    }

    public function test_get_stats_returns_counts(): void
    {
        $referrer = User::factory()->create();
        $newUser  = User::factory()->create();
        $code     = $this->service->getOrCreateCode($referrer);
        $this->service->trackRegistration($newUser, $code);

        $stats = $this->service->getStats($referrer);

        $this->assertEquals(1, $stats['total_invited']);
        $this->assertEquals($code, $stats['referral_code']);
    }
}
