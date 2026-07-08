<?php

namespace App\Services;

use App\Models\AnalyticsDaily;
use App\Models\CourseEnrollment;
use App\Models\QuizAttempt;
use App\Models\Story;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStats;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function aggregateDaily(): AnalyticsDaily
    {
        $today = now()->startOfDay();
        $yesterday = now()->subDay()->startOfDay();

        $data = [
            'date'                 => $today,
            'total_users'          => User::count(),
            'new_users'            => User::where('created_at', '>=', $today)->count(),
            'active_users'         => UserStats::whereDate('last_activity_date', $today)->count(),
            'enrollments'          => CourseEnrollment::whereDate('created_at', $today)->count(),
            'lessons_completed'    => DB::table('lesson_completions')->whereDate('completed_at', $today)->count(),
            'quiz_attempts'        => QuizAttempt::whereDate('created_at', $today)->count(),
            'quizzes_passed'       => QuizAttempt::whereDate('created_at', $today)->where('passed', true)->count(),
            'revenue'              => Transaction::where('type', 'purchase')
                ->where('status', 'completed')
                ->whereDate('created_at', $today)
                ->sum('amount'),
            'achievements_unlocked' => UserBadge::whereDate('earned_at', $today)->count(),
            'stories_created'       => Story::whereDate('created_at', $today)->count(),
        ];

        return AnalyticsDaily::updateOrCreate(
            ['date' => $today],
            $data
        );
    }

    public function getDailyRange(string $startDate, string $endDate): mixed
    {
        return AnalyticsDaily::whereBetween('date', [$startDate, $endDate])
            ->orderBy('date')
            ->get();
    }

    public function getTeacherStats(int $teacherId): array
    {
        return Cache::remember("teacher_analytics:{$teacherId}", 300, function () use ($teacherId) {
            $courseIds = DB::table('courses')
                ->where('teacher_id', $teacherId)
                ->pluck('id');

            $totalCourses = $courseIds->count();
            $totalStudents = CourseEnrollment::whereIn('course_id', $courseIds)
                ->distinct('student_id')
                ->count('student_id');
            $totalEnrollments = CourseEnrollment::whereIn('course_id', $courseIds)->count();
            $completedEnrollments = CourseEnrollment::whereIn('course_id', $courseIds)
                ->whereNotNull('completed_at')
                ->count();

            $revenue = Transaction::whereIn('course_id', $courseIds)
                ->where('type', 'enrollment')
                ->where('status', 'completed')
                ->sum('amount');

            $revenueThisMonth = Transaction::whereIn('course_id', $courseIds)
                ->where('type', 'enrollment')
                ->where('status', 'completed')
                ->whereMonth('created_at', now()->month)
                ->sum('amount');

            $quizAttempts = QuizAttempt::whereIn('quiz_id', function ($q) use ($courseIds) {
                $q->select('id')->from('quizzes')->whereIn('course_id', $courseIds);
            });

            $totalQuizAttempts = (clone $quizAttempts)->count();
            $passedQuizAttempts = (clone $quizAttempts)->where('passed', true)->count();

            return [
                'courses' => [
                    'total' => $totalCourses,
                ],
                'students' => [
                    'total'       => $totalStudents,
                    'enrollments' => $totalEnrollments,
                    'completions' => $completedEnrollments,
                    'completion_rate' => $totalEnrollments > 0
                        ? round(($completedEnrollments / $totalEnrollments) * 100, 1) : 0,
                ],
                'revenue' => [
                    'total'       => (float) $revenue,
                    'this_month'  => (float) $revenueThisMonth,
                ],
                'quizzes' => [
                    'total_attempts' => $totalQuizAttempts,
                    'passed'         => $passedQuizAttempts,
                    'pass_rate'      => $totalQuizAttempts > 0
                        ? round(($passedQuizAttempts / $totalQuizAttempts) * 100, 1) : 0,
                ],
            ];
        });
    }
}
