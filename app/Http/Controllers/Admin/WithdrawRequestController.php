<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WithdrawRequest;
use Illuminate\Http\Request;

class WithdrawRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = WithdrawRequest::with(['user', 'processor']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $requests  = $query->latest()->paginate(20);
        $totalPending  = WithdrawRequest::where('status', 'pending')->count();
        $totalPaid     = WithdrawRequest::where('status', 'paid')->sum('amount');
        $totalRejected = WithdrawRequest::where('status', 'rejected')->count();

        return view('admin.withdraw-requests', compact('requests', 'totalPending', 'totalPaid', 'totalRejected'));
    }

    public function approve(Request $request, $id)
    {
        $wr = WithdrawRequest::findOrFail($id);

        if ($wr->status !== 'pending') {
            return back()->with('error', 'هذا الطلب تمت معالجته مسبقاً');
        }

        $wr->update([
            'status'       => 'paid',
            'processed_by' => $request->user()->id,
            'processed_at' => now(),
        ]);

        return back()->with('success', 'تمت الموافقة على طلب السحب وتم تحديد حالته كـ "مدفوع"');
    }

    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $wr = WithdrawRequest::findOrFail($id);

        if ($wr->status !== 'pending') {
            return back()->with('error', 'هذا الطلب تمت معالجته مسبقاً');
        }

        $wr->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
            'processed_by'     => $request->user()->id,
            'processed_at'     => now(),
        ]);

        return back()->with('success', 'تم رفض طلب السحب');
    }
}
