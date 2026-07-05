<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    /**
     * List all coupons.
     */
    public function index()
    {
        $coupons = Coupon::latest()->paginate(20);
        return view('admin.coupons', compact('coupons'));
    }

    /**
     * Store a new coupon.
     */
    public function store(Request $request)
    {
        $request->validate([
            'code' => 'required|string|unique:coupons',
            'name' => 'required|string',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric',
            'usage_limit' => 'nullable|integer',
        ]);

        Coupon::create($request->only([
            'code', 'name', 'discount_type', 'discount_value', 'usage_limit',
        ]));

        return back()->with('success', 'تم إضافة الكوبون بنجاح');
    }
}
