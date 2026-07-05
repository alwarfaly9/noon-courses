<?php

namespace App\Http\Controllers;

use App\Models\LessonComment;
use App\Models\UserDailyGoal;
use App\Models\UserStats;
use App\Services\RecommendationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * PersonalizedDashboardController
 *
 * GET /api/v1/student/dashboard
 *
 * Returns a single, cacheable payload that powers the smart home screen:
 *  - continue_learning   (in-progress enrollments)
 *  - progress            (XP, level, streak)
 *  - daily_goal          (today's goal progress)
 *  - recommendations     (courses, paths, skills)
 *  - community_activity  (recent replies to user's comments)
 *  - motivational_card   (dynamic message based on state)
 */
class PersonalizedDashboardController extends Controller
{
    public function __construct(private readonly RecommendationService $recommendations) {}

    public function index(Request $request)
    {
        $user = $request->user();

        // Per-user cache — invalidated by GamificationService when XP changes
        $cacheKey = "dashboard:{$user->id}";

        $data = Cache::remember($cacheKey, 90, function () use ($user) {
            return [
                'continue_learning'  => $this->getContinueLearning($user->id),
                'progress'           => $this->getProgress($user->id),
                'daily_goal'         => $this->getDailyGoal($user->id),
                'recommendations'    => $this->getRecommendations($user->id),
                'community_activity' => $this->getCommunityActivity($user->id),
                'motivational_card'  => $this->getMotivationalCard($user->id),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /**
     * PATCH /api/v1/student/dashboard/goals
     * Lets the student adjust their daily targets.
     */
    public function updateGoals(Request $request)
    {
        $validated = $request->validate([
            'xp_target'      => 'sometimes|integer|min:10|max:500',
            'lessons_target' => 'sometimes|integer|min:1|max:20',
        ]);

        $goal = UserDailyGoal::todayFor($request->user()->id);
        $goal->update($validated);

        // Bust dashboard cache
        Cache::forget("dashboard:{$request->user()->id}");

        return response()->json(['success' => true, 'data' => $goal]);
    }

    // ── Private builders ──────────────────────────────────────────────────────

    private function getContinueLearning(int $userId): array
    {
        $enrollments = $this->recommendations->continueLearning($userId);

        return $enrollments->map(fn($e) => [
            'enrollment_id'      => $e->id,
            'course_id'          => $e->course_id,
            'title'              => $e->course->title ?? '',
            'image'              => $e->course->image ?? null,
            'category'           => $e->course->category->name ?? null,
            'progress_percentage' => (float) $e->progress_percentage,
            'last_activity_at'   => $e->updated_at,
        ])->values()->toArray();
    }

    private function getProgress(int $userId): array
    {
        $stats = UserStats::firstOrCreate(['user_id' => $userId]);
        $activity = $this->recommendations->weeklyActivity($userId);

        return [
            'xp_total'               => $stats->xp_total,
            'xp_this_week'           => $stats->xp_this_week,
            'level'                  => $stats->level,
            'level_progress_percent' => $stats->level_progress_percent,
            'xp_to_next_level'       => $stats->xp_to_next_level,
            'current_streak_days'    => $stats->current_streak_days,
            'longest_streak_days'    => $stats->longest_streak_days,
            'is_active_today'        => $stats->last_activity_date?->isToday() ?? false,
            'weekly_activity'        => $activity,  // [int × 7, Mon→Sun]
            'courses_completed'      => $stats->courses_completed,
            'paths_completed'        => $stats->paths_completed,
        ];
    }

    private function getDailyGoal(int $userId): array
    {
        $goal = UserDailyGoal::todayFor($userId);
        return [
            'xp_target'              => $goal->xp_target,
            'xp_earned_today'        => $goal->xp_earned_today,
            'xp_progress_percent'    => $goal->xp_progress_percent,
            'lessons_target'         => $goal->lessons_target,
            'lessons_done_today'     => $goal->lessons_done_today,
            'lessons_progress_percent' => $goal->lessons_progress_percent,
            'xp_goal_met'            => $goal->isXpGoalMet(),
            'lessons_goal_met'       => $goal->isLessonsGoalMet(),
        ];
    }

    private function getRecommendations(int $userId): array
    {
        return [
            'courses' => $this->recommendations->recommendCourses($userId, 6),
            'paths'   => $this->recommendations->recommendPaths($userId, 3),
            'skills'  => $this->recommendations->recommendSkills($userId, 6),
        ];
    }

    private function getCommunityActivity(int $userId): array
    {
        // Recent replies to comments the user made
        $replyIds = LessonComment::where('user_id', $userId)
            ->pluck('id');

        $replies = LessonComment::whereIn('parent_id', $replyIds)
            ->with('user:id,name,avatar', 'lesson:id,title')
            ->where('created_at', '>=', now()->subDays(7))
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(fn($r) => [
                'comment_id'  => $r->id,
                'from_name'   => $r->user->name ?? '',
                'from_avatar' => $r->user->avatar ?? null,
                'content'     => \Illuminate\Support\Str::limit($r->content, 80),
                'lesson_title' => $r->lesson->title ?? '',
                'created_at'  => $r->created_at,
            ]);

        return $replies->values()->toArray();
    }

    private function getMotivationalCard(int $userId): array
    {
        $stats = UserStats::where('user_id', $userId)->first();
        $streak = $stats?->current_streak_days ?? 0;
        $level  = $stats?->level ?? 1;

        // Priority: streak risk > milestone > general encouragement
        if ($streak > 0 && !($stats?->last_activity_date?->isToday())) {
            return [
                'type'    => 'streak_risk',
                'icon'    => 'fire',
                'title'   => 'لا تكسر سلسلتك!',
                'message' => "لديك سلسلة {$streak} يوم. أكمل درساً الآن للحفاظ عليها.",
                'action'  => 'continue_learning',
            ];
        }

        if ($streak >= 7 && $streak % 7 === 0) {
            return [
                'type'    => 'milestone',
                'icon'    => 'trophy',
                'title'   => "🎉 {$streak} يوم متواصل!",
                'message' => 'أنت بطل! استمر في هذا الإيقاع المذهل.',
                'action'  => null,
            ];
        }

        $messages = [
            ['title' => 'ابدأ يومك بتعلم شيء جديد!', 'message' => 'حتى 10 دقائق يومياً تصنع فرقاً كبيراً في مسيرتك.'],
            ['title' => 'أنت أقرب مما تظن', 'message' => 'كل درس يكتمل هو خطوة نحو هدفك.'],
            ['title' => 'المعرفة استثمار لا يُهدر', 'message' => 'تعلم اليوم ما يحتاجه سوق العمل غداً.'],
        ];

        $pick = $messages[$userId % count($messages)];
        return array_merge($pick, ['type' => 'encouragement', 'icon' => 'star', 'action' => null]);
    }
}
