<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawController extends Controller
{
    /**
     * Show teacher's own withdraw requests.
     */
    public function index()
    {
        $userId = Auth::id();

        $requests = WithdrawRequest::where('user_id', $userId)
                        ->latest()
                        ->paginate(20);

        $totalPending = WithdrawRequest::where('user_id', $userId)
                            ->where('status', 'pending')
                            ->count();

        $totalPaid = WithdrawRequest::where('user_id', $userId)
                            ->where('status', 'paid')
                            ->sum('amount');

        // Available balance = total earnings - total paid/pending withdrawals
        $courseIds = Course::where('teacher_id', $userId)->pluck('id');
        $totalEarnings = CourseEnrollment::whereIn('course_id', $courseIds)
                            ->where('status', 'active')
                            ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
                            ->sum('courses.price');

        $totalWithdrawn = WithdrawRequest::where('user_id', $userId)
                            ->whereIn('status', ['pending', 'paid'])
                            ->sum('amount');

        $availableBalance = max(0, $totalEarnings - $totalWithdrawn);

        return view('teacher.withdraw-requests', compact(
            'requests', 'totalPending', 'totalPaid', 'availableBalance'
        ));
    }

    /**
     * Submit a new withdraw request.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'amount'         => 'required|numeric|min:1',
            'bank_name'      => 'required|string|max:255',
            'account_name'   => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'iban'           => 'nullable|string|max:50',
        ]);

        WithdrawRequest::create([
            'user_id'        => Auth::id(),
            'amount'         => $data['amount'],
            'bank_name'      => $data['bank_name'],
            'account_name'   => $data['account_name'],
            'account_number' => $data['account_number'],
            'iban'           => $data['iban'] ?? null,
            'status'         => 'pending',
        ]);

        return redirect()->route('teacher.withdraw-requests')
                         ->with('success', 'تم إرسال طلب السحب بنجاح');
    }
}
