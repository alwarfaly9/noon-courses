<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    public function index()
    {
        $totalReferrals  = Referral::count();
        $pendingCount    = Referral::where('status', 'pending')->count();
        $rewardedCount   = Referral::where('status', 'rewarded')->count();
        $totalRewards    = Referral::where('status', 'rewarded')->sum('reward_amount');
        $conversionRate  = $totalReferrals > 0
            ? round(($rewardedCount / $totalReferrals) * 100, 1) : 0;

        $topReferrers = User::select('users.id', 'users.name', 'users.avatar')
            ->selectRaw('COUNT(referrals.id) as total_referrals')
            ->selectRaw('SUM(CASE WHEN referrals.status = "rewarded" THEN 1 ELSE 0 END) as successful')
            ->selectRaw('COALESCE(SUM(referrals.reward_amount), 0) as total_earned')
            ->join('referrals', 'users.id', '=', 'referrals.referrer_id')
            ->groupBy('users.id', 'users.name', 'users.avatar')
            ->orderByDesc('successful')
            ->limit(10)
            ->get();

        $monthlyData = Referral::selectRaw(
                "DATE_FORMAT(created_at, '%Y-%m') as month"
            )
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN status = "rewarded" THEN 1 ELSE 0 END) as rewarded')
            ->groupByRaw("DATE_FORMAT(created_at, '%Y-%m')")
            ->orderBy('month')
            ->get();

        $settings = ReferralSetting::current();

        return view('admin.referrals', compact(
            'totalReferrals', 'pendingCount', 'rewardedCount',
            'totalRewards', 'conversionRate', 'topReferrers',
            'monthlyData', 'settings'
        ));
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'reward_amount'        => 'required|numeric|min:0',
            'reward_type'          => 'required|in:wallet,xp,both',
            'xp_reward'            => 'integer|min:0',
            'max_rewards_per_user' => 'nullable|integer|min:1',
            'max_rewards_total'    => 'nullable|integer|min:1',
        ]);

        $settings = ReferralSetting::current();
        $settings->update($data);

        return back()->with('success', 'تم تحديث إعدادات الإحالة');
    }
}
