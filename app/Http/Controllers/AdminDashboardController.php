<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Transaction;
use App\Models\CreditCard;
use App\Models\Coupon;
use App\Models\SupportTicket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class AdminDashboardController extends Controller
{
    public function dashboard()
    {
        $user = auth()->user();

        if ($user->hasRole('teacher')) {
            return $this->teacherDashboard($user);
        }

        $stats = Cache::remember('admin_dashboard_stats', 300, function () {
            return [
                'total_users' => User::count(),
                'total_courses' => Course::count(),
                'published_courses' => Course::where('status', 'published')->count(),
                'pending_courses' => Course::where('status', 'pending')->count(),
                'total_revenue' => Transaction::where('status', 'completed')->sum('amount'),
                'total_transactions' => Transaction::count(),
                'active_credit_cards' => CreditCard::where('status', 'active')->count(),
                'pending_tickets' => SupportTicket::where('status', 'open')->count(),
            ];
        });

        // Revenue chart data (MySQL compatible)
        $revenueData = Transaction::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, SUM(amount) as total')
            ->where('status', 'completed')
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('month', 'asc')
            ->get();

        // User growth chart data (MySQL compatible)
        $userData = User::selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
            ->groupByRaw('DATE_FORMAT(created_at, "%Y-%m")')
            ->orderBy('month', 'asc')
            ->get();

        // Recent transactions
        $recentTransactions = Transaction::with('user')
            ->latest()
            ->take(5)
            ->get();

        // Pending courses
        $pendingCourses = Course::with('teacher')
            ->where('status', 'pending')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'revenueData', 'userData', 'recentTransactions', 'pendingCourses'));
    }

    private function teacherDashboard($user)
    {
        // Teacher specific stats
        $myCourses = Course::where('teacher_id', $user->id);

        $myStudentsCount = \DB::table('course_enrollments')
            ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
            ->where('courses.teacher_id', $user->id)
            ->distinct('course_enrollments.student_id')
            ->count();
            
        // Earnings calculation
        $totalEarnings = Transaction::where('status', 'completed')
            ->whereHas('course', function($q) use ($user) {
                 $q->where('teacher_id', $user->id);
            })
            ->sum('instructor_earnings');

        $stats = [
            'total_courses' => $myCourses->count(),
            'published_courses' => $myCourses->where('status', 'published')->count(),
            'pending_courses' => $myCourses->where('status', 'pending')->count(),
            'total_students' => $myStudentsCount,
            'total_earnings' => $totalEarnings,
        ];

        // Recent Enrollments for this teacher
        $recentEnrollments = \DB::table('course_enrollments')
            ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
            ->join('users', 'course_enrollments.student_id', '=', 'users.id')
            ->where('courses.teacher_id', $user->id)
            ->select('users.name as student_name', 'courses.title as course_title', 'course_enrollments.created_at', 'users.email')
            ->orderBy('course_enrollments.created_at', 'desc')
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentEnrollments'));
    }
}
