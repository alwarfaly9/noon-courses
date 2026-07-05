<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        // Date range: default = last 12 months
        $months = (int) $request->get('months', 12);
        $months = in_array($months, [3, 6, 12, 24]) ? $months : 12;
        $since  = now()->subMonths($months)->startOfMonth();

        // ── Summary KPIs ──────────────────────────────────────────────────────
        $kpis = Cache::remember("admin_reports_kpis_{$months}", 180, function () use ($since) {
            return [
                'total_revenue'     => Transaction::where('status', 'completed')->where('created_at', '>=', $since)->sum('amount'),
                'platform_earnings' => Transaction::where('status', 'completed')->where('created_at', '>=', $since)->sum('platform_commission'),
                'total_enrollments' => CourseEnrollment::where('created_at', '>=', $since)->count(),
                'new_users'         => User::where('created_at', '>=', $since)->count(),
                'new_courses'       => Course::where('created_at', '>=', $since)->count(),
                'pending_withdraws' => WithdrawRequest::where('status', 'pending')->sum('amount'),
            ];
        });

        // ── Revenue by month ──────────────────────────────────────────────────
        $revenueByMonth = Transaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total, SUM(platform_commission) as commission')
            ->where('status', 'completed')
            ->where('created_at', '>=', $since)
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('month')
            ->get();

        // ── Enrollments by month ──────────────────────────────────────────────
        $enrollmentsByMonth = CourseEnrollment::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', $since)
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('month')
            ->get();

        // ── Users by month ────────────────────────────────────────────────────
        $usersByMonth = User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->where('created_at', '>=', $since)
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('month')
            ->get();

        // ── Top 10 courses by enrollment ──────────────────────────────────────
        $topCourses = Course::with('teacher', 'category')
            ->withCount('students')
            ->where('status', 'published')
            ->orderByDesc('students_count')
            ->take(10)
            ->get();

        // ── Top 10 teachers by earnings ───────────────────────────────────────
        $topTeachers = User::whereHas('roles', fn($q) => $q->where('name', 'teacher'))
            ->withCount(['courses as published_courses_count' => fn($q) => $q->where('status', 'published')])
            ->withSum(['transactions as total_earnings' => fn($q) => $q->where('status', 'completed')], 'instructor_earnings')
            ->having('total_earnings', '>', 0)
            ->orderByDesc('total_earnings')
            ->take(10)
            ->get();

        // ── Enrollments by category ───────────────────────────────────────────
        $enrollmentsByCategory = DB::table('course_enrollments')
            ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
            ->join('categories', 'courses.category_id', '=', 'categories.id')
            ->where('course_enrollments.created_at', '>=', $since)
            ->selectRaw('categories.name, COUNT(*) as count')
            ->groupBy('categories.name')
            ->orderByDesc('count')
            ->get();

        return view('admin.reports', compact(
            'kpis', 'months',
            'revenueByMonth', 'enrollmentsByMonth', 'usersByMonth',
            'topCourses', 'topTeachers', 'enrollmentsByCategory'
        ));
    }
}
