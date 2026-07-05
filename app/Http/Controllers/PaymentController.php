<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\PaymentService;
use App\Http\Requests\RedeemCreditCardRequest;
use App\Http\Requests\GenerateCreditCardsRequest;

use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    private $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    // Get user credit balance
    public function getCreditBalance(Request $request)
    {
        $user = $request->user();
        $credit = $user->credits;

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $credit ? $credit->balance : 0
            ]
        ]);
    }

    // Redeem credit card
    public function redeemCreditCard(RedeemCreditCardRequest $request)
    {
        try {
            $result = $this->paymentService->redeemCreditCard($request->user(), $request->serial_number);
            return response()->json([
                'success' => true,
                'message' => 'Credit card redeemed successfully',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Get my transactions
    public function myTransactions(Request $request)
    {
        $user = $request->user();
        
        $transactions = Transaction::where('user_id', $user->id)
            ->with(['course', 'coupon'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    // Admin: Get all transactions
    public function allTransactions(Request $request)
    {
        $transactions = Transaction::with(['user', 'course', 'coupon'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    // Admin: Get credit cards
    public function creditCards(Request $request)
    {
        $query = CreditCard::query();

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $cards = $query->with(['creator', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $cards
        ]);
    }

    // Admin: Generate credit cards
    public function generateCreditCards(GenerateCreditCardsRequest $request)
    {
        $cards = $this->paymentService->generateCards(
            $request->user(),
            $request->validated('count'),
            $request->validated('value')
        );

        return response()->json([
            'success' => true,
            'message' => 'Credit cards generated successfully',
            'data' => [
                'cards' => $cards
            ]
        ]);
    }

    // Student: Validate a coupon code against a course price and return the discounted price
    public function validateCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code'      => 'required|string',
            'course_id' => 'required|integer|exists:courses,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $coupon = \App\Models\Coupon::where('code', $request->code)
            ->where('is_active', true)
            ->first();

        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'كود الخصم غير صالح أو منتهي الصلاحية',
            ], 404);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            return response()->json([
                'success' => false,
                'message' => 'انتهت صلاحية كود الخصم',
            ], 410);
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            return response()->json([
                'success' => false,
                'message' => 'تم استنفاد الحد الأقصى لاستخدام هذا الكود',
            ], 410);
        }

        $course = \App\Models\Course::findOrFail($request->course_id);
        $originalPrice = $course->discount_price ?? $course->price ?? 0;

        if ($coupon->minimum_purchase && $originalPrice < $coupon->minimum_purchase) {
            return response()->json([
                'success' => false,
                'message' => "الحد الأدنى للشراء باستخدام هذا الكود هو {$coupon->minimum_purchase} LYD",
            ], 422);
        }

        if ($coupon->discount_type === 'percentage') {
            $discount = ($originalPrice * $coupon->discount_value) / 100;
            if ($coupon->maximum_discount) {
                $discount = min($discount, $coupon->maximum_discount);
            }
        } else {
            $discount = min($coupon->discount_value, $originalPrice);
        }

        $finalPrice = max(0, $originalPrice - $discount);

        return response()->json([
            'success' => true,
            'message' => 'كود الخصم صالح',
            'data' => [
                'coupon_id'      => $coupon->id,
                'coupon_name'    => $coupon->name,
                'original_price' => $originalPrice,
                'discount'       => round($discount, 2),
                'final_price'    => round($finalPrice, 2),
            ],
        ]);
    }

    // Admin: Get withdraw requests
    public function withdrawRequests(Request $request)
    {
        $requests = \App\Models\WithdrawRequest::with(['user', 'processor'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests
        ]);
    }

    // Admin: Approve withdraw
    public function approveWithdraw(Request $request, $id)
    {
        $withdrawRequest = \App\Models\WithdrawRequest::findOrFail($id);
        
        if ($withdrawRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed'
            ], 400);
        }

        $user = $request->user();

        $withdrawRequest->update([
            'status' => 'paid',
            'processed_by' => $user->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdraw request approved and marked as paid'
        ]);
    } // End approveWithdraw

    // Admin: Reject withdraw
    public function rejectWithdraw(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $withdrawRequest = \App\Models\WithdrawRequest::findOrFail($id);
        
        if ($withdrawRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'This request has already been processed'
            ], 400);
        }

        $user = $request->user();

        $withdrawRequest->update([
            'status' => 'rejected',
            'rejection_reason' => $request->reason,
            'processed_by' => $user->id,
            'processed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdraw request rejected'
        ]);
    }

    // Teacher: List own withdraw requests
    public function myWithdrawRequests(Request $request)
    {
        $requests = \App\Models\WithdrawRequest::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }

    // Teacher: Submit a new withdraw request
    public function requestWithdraw(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount'         => 'required|numeric|min:10',
            'bank_name'      => 'required|string|max:100',
            'account_name'   => 'required|string|max:150',
            'account_number' => 'required|string|max:50',
            'iban'           => 'nullable|string|max:34',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صالحة',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // Prevent duplicate pending requests
        $hasPending = \App\Models\WithdrawRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'لديك طلب سحب معلق بالفعل، يرجى انتظار معالجته أولاً',
            ], 409);
        }

        // Check teacher has enough balance
        $credit = $user->credits;
        $balance = $credit ? (float) $credit->balance : 0;
        if ($balance < (float) $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'رصيدك غير كافٍ لطلب هذا المبلغ',
            ], 422);
        }

        $withdraw = \App\Models\WithdrawRequest::create([
            'user_id'        => $user->id,
            'amount'         => $request->amount,
            'bank_name'      => $request->bank_name,
            'account_name'   => $request->account_name,
            'account_number' => $request->account_number,
            'iban'           => $request->iban,
            'status'         => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب السحب بنجاح، سيتم مراجعته خلال 3-5 أيام عمل',
            'data'    => $withdraw,
        ], 201);
    }

    // Coupons Management
    public function coupons(Request $request)
    {
        $coupons = Coupon::orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $coupons
        ]);
    }

    public function createCoupon(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string|unique:coupons',
            'name' => 'required|string',
            'discount_type' => 'required|in:percentage,fixed_amount',
            'discount_value' => 'required|numeric',
            'minimum_purchase' => 'nullable|numeric',
            'maximum_discount' => 'nullable|numeric',
            'usage_limit' => 'nullable|integer',
            'expires_at' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon = Coupon::create($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Coupon created successfully',
            'data' => $coupon
        ], 201);
    }

    public function updateCoupon(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'code' => 'sometimes|string|unique:coupons,code,' . $id,
            'name' => 'sometimes|string',
            'discount_type' => 'sometimes|in:percentage,fixed_amount',
            'discount_value' => 'sometimes|numeric',
            'is_active' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $coupon->update($validator->validated());

        return response()->json([
            'success' => true,
            'message' => 'Coupon updated successfully',
            'data' => $coupon
        ]);
    }

    public function deleteCoupon($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();

        return response()->json([
            'success' => true,
            'message' => 'Coupon deleted successfully'
        ]);
    }
}
