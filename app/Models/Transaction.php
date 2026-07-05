<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_number',
        'user_id',
        'course_id',
        'type',
        'status',
        'amount',
        'platform_commission',
        'instructor_earnings',
        'payment_method',
        'payment_reference',
        'credit_card_id',
        'coupon_id',
        'notes',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'platform_commission' => 'decimal:2',
            'instructor_earnings' => 'decimal:2',
            'completed_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function creditCard()
    {
        return $this->belongsTo(CreditCard::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
