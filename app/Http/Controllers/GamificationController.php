<?php

namespace App\Http\Controllers;

use App\Models\Badge;
use App\Models\UserBadge;
use App\Models\UserStats;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GamificationController extends Controller
{
    public function __construct(private readonly GamificationService $service) {}

    /**
     * GET /api/v1/gamification/stats
     * Returns XP, level, streak, progress to next level.
     */
    public function stats(Request $request)
    {
        $user  = $request->user();
        $stats = UserStats::firstOrCreate(['user_id' => $user->id]);

        return response()->json([
            'success' => true,
            'data' => [
                'xp_total'              => $stats->xp_total,
                'xp_this_week'          => $stats->xp_this_week,
                'level'                 => $stats->level,
                'level_progress_percent' => $stats->level_progress_percent,
                'xp_to_next_level'      => $stats->xp_to_next_level,
                'current_streak_days'   => $stats->current_streak_days,
                'longest_streak_days'   => $stats->longest_streak_days,
                'last_activity_date'    => $stats->last_activity_date,
                'lessons_completed'     => $stats->lessons_completed,
                'courses_completed'     => $stats->courses_completed,
                'quizzes_passed'        => $stats->quizzes_passed,
                'paths_completed'       => $stats->paths_completed,
            ],
        ]);
    }

    /**
     * GET /api/v1/gamification/badges
     * Returns earned badges + locked badges (for showcase).
     */
    public function badges(Request $request)
    {
        $user = $request->user();

        $earned = UserBadge::where('user_id', $user->id)
            ->with('badge')
            ->orderByDesc('earned_at')
            ->get()
            ->map(fn($ub) => array_merge(
                $ub->badge->toArray(),
                ['earned_at' => $ub->earned_at]
            ));

        $earnedIds = $earned->pluck('id');

        $locked = Badge::where('is_active', true)
            ->whereNotIn('id', $earnedIds)
            ->orderBy('condition_value')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'earned' => $earned,
                'locked' => $locked,
            ],
        ]);
    }

    /**
     * GET /api/v1/gamification/leaderboard
     */
    public function leaderboard()
    {
        $board = Cache::remember('gamification_leaderboard', 120, function () {
            return $this->service->getLeaderboard(20);
        });

        return response()->json(['success' => true, 'data' => $board]);
    }

    /**
     * GET /api/v1/gamification/streaks
     */
    public function streaks(Request $request)
    {
        $stats = UserStats::firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json([
            'success' => true,
            'data' => [
                'current_streak_days' => $stats->current_streak_days,
                'longest_streak_days' => $stats->longest_streak_days,
                'last_activity_date'  => $stats->last_activity_date,
                'is_active_today'     => $stats->last_activity_date?->isToday() ?? false,
            ],
        ]);
    }
}
