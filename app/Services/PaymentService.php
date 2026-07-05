<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditCard;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function redeemCreditCard(User $user, string $serialNumber)
    {
        return DB::transaction(function () use ($user, $serialNumber) {
            $card = CreditCard::where('serial_number', $serialNumber)->first();

            if (!$card || $card->status !== 'active' || ($card->expires_at && $card->expires_at < now())) {
                throw new \Exception('Invalid or expired credit card');
            }

            $credit = $user->credits;
            if (!$credit) {
                $credit = Credit::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                ]);
            } else {
                $credit = Credit::where('user_id', $user->id)->lockForUpdate()->first();
            }

            $credit->increment('balance', $card->value);

            $card->update([
                'status' => 'used',
                'used_by' => $user->id,
                'used_at' => now(),
            ]);

            Transaction::create([
                'transaction_number' => 'TXN-' . strtoupper(uniqid()),
                'user_id' => $user->id,
                'type' => 'credit_purchase',
                'status' => 'completed',
                'amount' => $card->value,
                'payment_method' => 'credit_card',
                'payment_reference' => $card->serial_number,
                'credit_card_id' => $card->id,
                'completed_at' => now(),
            ]);

            return [
                'credit_added' => $card->value,
                'new_balance' => $credit->balance
            ];
        });
    }

    public function generateCards(User $admin, int $count, float $value)
    {
        $cards = [];
        for ($i = 0; $i < $count; $i++) {
            $serial = 'CARD-' . date('Y') . '-' . strtoupper(Str::random(10));
            $card = CreditCard::create([
                'serial_number' => $serial,
                'value' => $value,
                'status' => 'active',
                'created_by' => $admin->id,
                'expires_at' => now()->addYear(),
            ]);
            $cards[] = $card;
        }
        return $cards;
    }
}
