<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Transaction;
use App\Models\CourseEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Admin Dashboard
    public function adminDashboard(Request $request)
    {
        $data = Cache::remember('api_admin_dashboard', 300, function () {
            return [
                'total_users' => User::count(),
                'total_courses' => Course::count(),
                'published_courses' => Course::where('status', 'published')->count(),
                'pending_courses' => Course::where('status', 'pending')->count(),
                'total_students' => User::whereHas('roles', function($q) {
                    $q->where('name', 'student');
                })->count(),
                'total_teachers' => User::whereHas('roles', function($q) {
                    $q->where('name', 'teacher');
                })->count(),
                'total_revenue' => Transaction::where('status', 'completed')
                    ->where('type', '!=', 'refund')
                    ->sum('amount'),
                'monthly_revenue' => $this->getMonthlyRevenue(),
                'recent_transactions' => Transaction::with('user', 'course')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Teacher Dashboard
    public function teacherDashboard(Request $request)
    {
        $user = $request->user();

        $teacherCourses = Course::where('teacher_id', $user->id)->get();

        $teacherCourseIds = $teacherCourses->pluck('id');

        $totalStudents = CourseEnrollment::whereIn('course_id', $teacherCourseIds)
            ->distinct('student_id')
            ->count();

        $totalEarnings = Transaction::whereIn('course_id', $teacherCourseIds)
            ->where('status', 'completed')
            ->sum('instructor_earnings');

        $data = [
            'total_courses' => $teacherCourses->count(),
            'published_courses' => $teacherCourses->where('status', 'published')->count(),
            'pending_courses' => $teacherCourses->where('status', 'pending')->count(),
            'total_students' => $totalStudents,
            'total_earnings' => $totalEarnings,
            'recent_enrollments' => CourseEnrollment::whereIn('course_id', $teacherCourses->pluck('id'))
                ->with(['student', 'course'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Analytics
    public function analytics(Request $request)
    {
        $data = [
            'users' => [
                'total' => User::count(),
                'by_month' => $this->getUsersByMonth(),
            ],
            'courses' => [
                'total' => Course::count(),
                'by_status' => Course::select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->get(),
            ],
            'revenue' => [
                'total' => Transaction::where('status', 'completed')
                    ->where('type', '!=', 'refund')
                    ->sum('amount'),
                'by_month' => $this->getMonthlyRevenue(),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Course Analytics
    public function courseAnalytics(Request $request)
    {
        $data = [
            'most_popular' => Course::with(['teacher', 'category'])
                ->orderBy('students_count', 'desc')
                ->limit(10)
                ->get(),
            'highest_rated' => Course::with(['teacher', 'category'])
                ->orderBy('rating', 'desc')
                ->limit(10)
                ->get(),
            'most_revenue' => Transaction::select('course_id', DB::raw('sum(amount) as total'))
                ->where('status', 'completed')
                ->whereNotNull('course_id')
                ->groupBy('course_id')
                ->orderBy('total', 'desc')
                ->limit(10)
                ->with('course.teacher')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // User Analytics
    public function userAnalytics(Request $request)
    {
        $data = [
            'by_role' => User::select('roles.name', DB::raw('count(*) as count'))
                ->join('user_roles', 'users.id', '=', 'user_roles.user_id')
                ->join('roles', 'user_roles.role_id', '=', 'roles.id')
                ->groupBy('roles.name')
                ->get(),
            'most_active' => User::with('roles')
                ->orderBy('last_login_at', 'desc')
                ->limit(10)
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    // Revenue Analytics
    public function revenueAnalytics(Request $request)
    {
        $data = [
            'total_revenue' => Transaction::where('status', 'completed')
                ->where('type', '!=', 'refund')
                ->sum('amount'),
            'total_commission' => Transaction::where('status', 'completed')
                ->sum('platform_commission'),
            'by_month' => $this->getMonthlyRevenue(),
            'by_course' => Transaction::select('course_id', DB::raw('sum(amount) as total'))
                ->where('status', 'completed')
                ->whereNotNull('course_id')
                ->groupBy('course_id')
                ->orderBy('total', 'desc')
                ->limit(20)
                ->with('course')
                ->get(),
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    private function getMonthlyRevenue()
    {
        return Transaction::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('sum(amount) as total')
            )
            ->where('status', 'completed')
            ->where('type', '!=', 'refund')
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
    }

    private function getUsersByMonth()
    {
        return User::select(
                DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
                DB::raw('count(*) as count')
            )
            ->groupBy('month')
            ->orderBy('month', 'desc')
            ->limit(12)
            ->get();
    }
}
