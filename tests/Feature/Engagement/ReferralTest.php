<?php

namespace Tests\Feature\Engagement;

use App\Models\Referral;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ReferralTest extends TestCase
{
    use RefreshDatabase;

    private User $referrer;
    private User $newUser;
    private ReferralService $referralService;

    protected function setUp(): void
    {
        parent::setUp();
        Role::firstOrCreate(['name' => 'student', 'guard_name' => 'web']);

        $this->referrer = User::factory()->create();
        $this->referrer->assignRole('student');

        $this->newUser = User::factory()->create();
        $this->newUser->assignRole('student');

        $this->referralService = app(ReferralService::class);
    }

    public function test_user_can_generate_referral_code(): void
    {
        $code = $this->referralService->getOrCreateCode($this->referrer);

        $this->assertNotNull($code);
        $this->assertEquals(8, strlen($code));
    }

    public function test_referral_is_tracked(): void
    {
        $code = $this->referralService->getOrCreateCode($this->referrer);
        $result = $this->referralService->trackRegistration($this->newUser, $code);

        $this->assertTrue($result);
        $this->assertDatabaseHas('referrals', [
            'referrer_id' => $this->referrer->id,
            'referred_id' => $this->newUser->id,
            'status'      => 'pending',
        ]);
    }

    public function test_self_referral_is_prevented(): void
    {
        $code = $this->referralService->getOrCreateCode($this->referrer);
        $result = $this->referralService->trackRegistration($this->referrer, $code);

        $this->assertFalse($result);
    }

    public function test_duplicate_referral_is_prevented(): void
    {
        $code = $this->referralService->getOrCreateCode($this->referrer);

        $this->referralService->trackRegistration($this->newUser, $code);
        $result = $this->referralService->trackRegistration($this->newUser, $code);

        $this->assertFalse($result);
    }

    public function test_referral_reward_is_granted_on_conversion(): void
    {
        $code = $this->referralService->getOrCreateCode($this->referrer);
        $this->referralService->trackRegistration($this->newUser, $code);

        // Seed referral settings
        \App\Models\ReferralSetting::create([
            'reward_amount' => 10.00,
            'reward_type'   => 'wallet',
            'is_active'     => true,
        ]);

        $this->referralService->convertReferral($this->newUser);

        $this->assertDatabaseHas('referrals', [
            'referred_id'   => $this->newUser->id,
            'status'        => 'rewarded',
        ]);

        $credit = \App\Models\Credit::where('user_id', $this->referrer->id)->first();
        $this->assertEquals(10.00, $credit->balance);
    }

    public function test_stats_returns_correct_counts(): void
    {
        $code = $this->referralService->getOrCreateCode($this->referrer);
        $this->referralService->trackRegistration($this->newUser, $code);

        $stats = $this->referralService->getStats($this->referrer);

        $this->assertEquals(1, $stats['total_invited']);
        $this->assertEquals($code, $stats['referral_code']);
    }
}
