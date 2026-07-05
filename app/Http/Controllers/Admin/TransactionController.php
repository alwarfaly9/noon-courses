<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditCard;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    /**
     * List all transactions.
     */
    public function index(Request $request)
    {
        $query = Transaction::with('user');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $transactions = $query->latest()->paginate(20);
        return view('admin.transactions', compact('transactions'));
    }

    /**
     * List credit cards.
     */
    public function creditCards(Request $request)
    {
        $query = CreditCard::with('user');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->value) {
            $query->where('value', $request->value);
        }

        $creditCards = $query->latest()->paginate(20);
        return view('admin.credit-cards', compact('creditCards'));
    }

    /**
     * Generate credit cards.
     */
    public function generateCreditCards(Request $request)
    {
        $request->validate([
            'count' => 'required|integer|min:1|max:1000',
            'value' => 'required|numeric|in:10,25,50,100,250',
        ]);

        $cards = [];
        for ($i = 0; $i < $request->count; $i++) {
            $serial = 'CARD-' . date('Y') . '-' . strtoupper(substr(md5(random_bytes(16)), 0, 6));
            $cards[] = CreditCard::create([
                'serial_number' => $serial,
                'value' => $request->value,
                'status' => 'active',
                'created_by' => Auth::id(),
                'expires_at' => now()->addYear(),
            ]);
        }

        return back()->with('success', "تم توليد {$request->count} كرت برصيد {$request->value} د.ل");
    }
}
