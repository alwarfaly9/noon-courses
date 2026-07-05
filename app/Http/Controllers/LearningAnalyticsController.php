<?php

namespace App\Http\Controllers;

use App\Models\CourseEnrollment;
use App\Models\LessonComment;
use App\Models\QuizAttempt;
use App\Models\UserStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * LearningAnalyticsController
 *
 * GET /api/v1/student/analytics
 *
 * Gives the student a personal learning intelligence report:
 *  - Completion velocity (lessons/week over past 4 weeks)
 *  - Quiz performance summary
 *  - Skill growth timeline
 *  - Community engagement score
 *  - Course completion funnel
 */
class LearningAnalyticsController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();
        $cKey  = "student_analytics:{$user->id}";

        $data = \Illuminate\Support\Facades\Cache::remember($cKey, 300, function () use ($user) {
            return [
                'completion_velocity' => $this->completionVelocity($user->id),
                'quiz_performance'    => $this->quizPerformance($user->id),
                'skill_growth'        => $this->skillGrowth($user->id),
                'engagement'          => $this->engagementSummary($user->id),
                'course_funnel'       => $this->courseFunnel($user->id),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /** Lessons completed per week for the past 4 weeks */
    private function completionVelocity(int $userId): array
    {
        $rows = DB::table('lesson_completions')
            ->where('user_id', $userId)
            ->where('completed_at', '>=', now()->subWeeks(4))
            ->selectRaw('YEARWEEK(completed_at, 1) as yw, COUNT(*) as cnt')
            ->groupBy('yw')
            ->orderBy('yw')
            ->pluck('cnt', 'yw');

        $result = [];
        for ($w = 3; $w >= 0; $w--) {
            $yw    = now()->subWeeks($w)->format('oW'); // ISO year+week
            $label = $w === 0 ? 'هذا الأسبوع' : "قبل {$w} أسبوع";
            $result[] = ['label' => $label, 'lessons' => (int) ($rows[$yw] ?? 0)];
        }
        return $result;
    }

    /** Overall quiz pass rate + average score */
    private function quizPerformance(int $userId): array
    {
        $attempts = QuizAttempt::where('user_id', $userId)
            ->whereNotNull('score')
            ->get(['score', 'passed']);

        if ($attempts->isEmpty()) {
            return ['total' => 0, 'passed' => 0, 'pass_rate' => 0, 'avg_score' => 0];
        }

        $total    = $attempts->count();
        $passed   = $attempts->where('passed', true)->count();
        $avgScore = round($attempts->avg('score'), 1);

        return [
            'total'     => $total,
            'passed'    => $passed,
            'pass_rate' => round(($passed / $total) * 100, 1),
            'avg_score' => $avgScore,
        ];
    }

    /** Skill acquisition timeline (last 10 skills earned) */
    private function skillGrowth(int $userId): array
    {
        return DB::table('user_skills')
            ->join('skills', 'user_skills.skill_id', '=', 'skills.id')
            ->where('user_skills.user_id', $userId)
            ->orderByDesc('user_skills.created_at')
            ->limit(10)
            ->get(['skills.name', 'user_skills.level', 'user_skills.created_at as earned_at'])
            ->toArray();
    }

    /** Community participation summary */
    private function engagementSummary(int $userId): array
    {
        $comments = LessonComment::where('user_id', $userId)->count();
        $likes    = DB::table('comment_reactions')
            ->whereIn('comment_id',
                LessonComment::where('user_id', $userId)->pluck('id')
            )->count();

        $stats = UserStats::where('user_id', $userId)->first();

        return [
            'comments_posted'        => $comments,
            'likes_received'         => $likes,
            'engagement_score'       => $stats?->engagement_score ?? 0,
            'helpful_votes_received' => $stats?->helpful_votes_received ?? 0,
        ];
    }

    /** Course enrollment funnel */
    private function courseFunnel(int $userId): array
    {
        $enrollments = CourseEnrollment::where('student_id', $userId)->get();

        $total     = $enrollments->count();
        $started   = $enrollments->where('progress_percentage', '>', 0)->count();
        $halfway   = $enrollments->where('progress_percentage', '>=', 50)->count();
        $completed = $enrollments->whereNotNull('completed_at')->count();

        return compact('total', 'started', 'halfway', 'completed');
    }
}
