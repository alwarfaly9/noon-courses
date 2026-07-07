<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\Referral;
use App\Models\ReferralSetting;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

/**
 * ReferralService
 *
 * Handles all referral lifecycle events:
 *  1. Code generation
 *  2. Registration tracking (pending → pending, referred_id set)
 *  3. Conversion on first enrollment (pending → converted, reward issued to referrer)
 *  4. Anti-abuse guards (one referral per referred user, no self-referral)
 */
class ReferralService
{
    // ── Code management ────────────────────────────────────────────────────────

    /** Returns the user's existing code, or generates and saves a new one. */
    public function getOrCreateCode(User $user): string
    {
        if ($user->referral_code) {
            return $user->referral_code;
        }

        $code = $this->generateUniqueCode();
        $user->update(['referral_code' => $code]);

        return $code;
    }

    private function generateUniqueCode(): string
    {
        do {
            // 8 char alphanumeric, uppercase, easy to read/share
            $code = strtoupper(Str::random(8));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    // ── Registration hook ──────────────────────────────────────────────────────

    /**
     * Called after a new user registers with a referral code.
     * Records the referral in pending state.
     *
     * @return bool  true if a valid referral was recorded
     */
    public function trackRegistration(User $newUser, string $referralCode): bool
    {
        $referrer = User::where('referral_code', $referralCode)->first();

        // Guards: referrer must exist, be different, and not already have referred this user
        if (!$referrer || $referrer->id === $newUser->id) {
            return false;
        }

        if (Referral::where('referred_id', $newUser->id)->exists()) {
            return false; // This user was already referred — no double-counting
        }

        Referral::create([
            'referrer_id' => $referrer->id,
            'referred_id' => $newUser->id,
            'status'      => 'pending',
        ]);

        return true;
    }

    // ── Conversion hook ────────────────────────────────────────────────────────

    /**
     * Called when a referred user completes their first enrollment.
     * Converts the referral and credits the referrer's wallet.
     */
    public function convertReferral(User $enrolledUser): void
    {
        $referral = Referral::where('referred_id', $enrolledUser->id)
            ->where('status', 'pending')
            ->first();

        if (!$referral) {
            return;
        }

        $settings = ReferralSetting::current();

        DB::transaction(function () use ($referral, $settings) {
            $referral->update([
                'status'        => 'rewarded',
                'reward_amount' => $settings->reward_amount,
                'converted_at'  => now(),
                'rewarded_at'   => now(),
            ]);

            $rewardAmount = $settings->reward_amount;

            if (in_array($settings->reward_type, ['wallet', 'both'])) {
                $credit = Credit::firstOrCreate(
                    ['user_id' => $referral->referrer_id],
                    ['balance' => 0]
                );
                $credit->increment('balance', $rewardAmount);
            }

            if (in_array($settings->reward_type, ['xp', 'both']) && $settings->xp_reward > 0) {
                app(GamificationService::class)->awardXp(
                    User::find($referral->referrer_id),
                    $settings->xp_reward,
                    'referral_reward'
                );
            }

            // Notify referrer
            NotificationService::send(
                $referral->referrer_id,
                '🎉 صديقك سجّل في دورة!',
                'حصلت على ' . $rewardAmount . ' دينار مكافأة إحالة. شكراً لدعوتك أصدقاءك!',
                'referral',
                ['action' => 'open_referrals']
            );
        });
    }

    // ── Stats ──────────────────────────────────────────────────────────────────

    public function getStats(User $user): array
    {
        $referrals = Referral::where('referrer_id', $user->id)->get();

        return [
            'referral_code'       => $this->getOrCreateCode($user),
            'total_invited'       => $referrals->count(),
            'successful'          => $referrals->whereIn('status', ['converted', 'rewarded'])->count(),
            'total_earned'        => $referrals->sum('reward_amount'),
            'share_url'           => config('app.url') . '/register?ref=' . $user->referral_code,
        ];
    }
}
