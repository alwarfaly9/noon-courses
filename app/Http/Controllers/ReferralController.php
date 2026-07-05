<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referrals) {}

    /** GET /referrals — My referral stats + code */
    public function stats(Request $request)
    {
        return response()->json([
            'success' => true,
            'data'    => $this->referrals->getStats($request->user()),
        ]);
    }

    /** POST /referrals/generate — Regenerate my code */
    public function generate(Request $request)
    {
        $user = $request->user();
        // Clear existing code so getOrCreateCode generates a fresh one
        $user->update(['referral_code' => null]);
        $code = $this->referrals->getOrCreateCode($user);

        return response()->json([
            'success'       => true,
            'referral_code' => $code,
            'share_url'     => config('app.url') . '/register?ref=' . $code,
        ]);
    }
}
