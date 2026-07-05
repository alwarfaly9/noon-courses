<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserStats;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * AdminAnalyticsController
 *
 * GET /api/v1/admin/analytics          — executive summary
 * GET /api/v1/admin/analytics/growth   — user + revenue growth
 * GET /api/v1/admin/analytics/learning — learning completion trends
 *
 * All endpoints require admin role.
 * Data cached for 10 minutes (600s) — acceptable staleness for executive views.
 */
class AdminAnalyticsController extends Controller
{
    /** Executive summary dashboard */
    public function summary()
    {
        $data = Cache::remember('admin_analytics_summary', 600, function () {
            $now      = now();
            $thisWeek = $now->copy()->startOfWeek();
            $lastWeek = $now->copy()->subWeek()->startOfWeek();

            $totalStudents       = User::role('student')->count();
            $newStudentsThisWeek = User::role('student')->where('created_at', '>=', $thisWeek)->count();
            $newStudentsLastWeek = User::role('student')
                ->whereBetween('created_at', [$lastWeek, $thisWeek])->count();

            $totalCourses     = Course::where('status', 'published')->count();
            $totalEnrollments = CourseEnrollment::count();
            $completedCourses = CourseEnrollment::whereNotNull('completed_at')->count();

            $revenueThisMonth = Transaction::where('type', 'purchase')
                ->where('status', 'completed')
                ->whereMonth('created_at', $now->month)
                ->whereYear('created_at', $now->year)
                ->sum('amount');

            $revenueLastMonth = Transaction::where('type', 'purchase')
                ->where('status', 'completed')
                ->whereMonth('created_at', $now->copy()->subMonth()->month)
                ->whereYear('created_at', $now->copy()->subMonth()->year)
                ->sum('amount');

            $activeUsersToday = UserStats::whereDate('last_activity_date', today())->count();

            $avgStreak = round(UserStats::where('current_streak_days', '>', 0)
                ->avg('current_streak_days') ?? 0, 1);

            $completionRate = $totalEnrollments > 0
                ? round(($completedCourses / $totalEnrollments) * 100, 1) : 0;

            return [
                'students' => [
                    'total'            => $totalStudents,
                    'new_this_week'    => $newStudentsThisWeek,
                    'new_last_week'    => $newStudentsLastWeek,
                    'week_growth_pct'  => $newStudentsLastWeek > 0
                        ? round((($newStudentsThisWeek - $newStudentsLastWeek) / $newStudentsLastWeek) * 100, 1)
                        : null,
                    'active_today'     => $activeUsersToday,
                ],
                'courses' => [
                    'total'           => $totalCourses,
                    'total_enrollments' => $totalEnrollments,
                    'completion_rate' => $completionRate,
                ],
                'revenue' => [
                    'this_month'      => (float) $revenueThisMonth,
                    'last_month'      => (float) $revenueLastMonth,
                    'month_growth_pct' => $revenueLastMonth > 0
                        ? round((($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100, 1)
                        : null,
                ],
                'engagement' => [
                    'avg_streak_days' => $avgStreak,
                ],
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** 30-day daily signups + revenue chart */
    public function growth()
    {
        $data = Cache::remember('admin_analytics_growth', 600, function () {
            $signups = DB::table('users')
                ->where('created_at', '>=', now()->subDays(29))
                ->selectRaw('DATE(created_at) as day, COUNT(*) as cnt')
                ->groupBy('day')
                ->pluck('cnt', 'day');

            $revenue = DB::table('transactions')
                ->where('type', 'purchase')
                ->where('status', 'completed')
                ->where('created_at', '>=', now()->subDays(29))
                ->selectRaw('DATE(created_at) as day, SUM(amount) as total')
                ->groupBy('day')
                ->pluck('total', 'day');

            $days = [];
            for ($i = 29; $i >= 0; $i--) {
                $d       = now()->subDays($i)->format('Y-m-d');
                $days[]  = [
                    'date'     => $d,
                    'signups'  => (int) ($signups[$d] ?? 0),
                    'revenue'  => (float) ($revenue[$d] ?? 0),
                ];
            }
            return $days;
        });

        return response()->json(['success' => true, 'data' => $data]);
    }

    /** Learning trends: top courses, completion rates, drop-off analysis */
    public function learning()
    {
        $data = Cache::remember('admin_analytics_learning', 600, function () {
            // Top 10 courses by enrollment
            $topCourses = Course::withCount('enrollments')
                ->with('category:id,name', 'teacher:id,name')
                ->where('status', 'published')
                ->orderByDesc('enrollments_count')
                ->limit(10)
                ->get(['id', 'title', 'category_id', 'teacher_id', 'rating', 'students_count']);

            // Weekly lesson completion velocity (platform-wide)
            $weeklyLessons = DB::table('lesson_completions')
                ->where('completed_at', '>=', now()->subWeeks(8))
                ->selectRaw('YEARWEEK(completed_at, 1) as yw, COUNT(*) as cnt')
                ->groupBy('yw')
                ->orderBy('yw')
                ->get();

            // Average progress at drop-off (for non-completed enrollments)
            $avgDropOff = CourseEnrollment::whereNull('completed_at')
                ->where('progress_percentage', '>', 0)
                ->avg('progress_percentage');

            return [
                'top_courses'    => $topCourses,
                'weekly_lessons' => $weeklyLessons,
                'avg_dropoff_pct' => round($avgDropOff ?? 0, 1),
            ];
        });

        return response()->json(['success' => true, 'data' => $data]);
    }
}
