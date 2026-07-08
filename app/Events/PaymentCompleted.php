<?php

namespace App\Events;

use App\Models\Course;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Foundation\Events\Dispatchable;

class PaymentCompleted
{
    use Dispatchable;

    public function __construct(
        public User $student,
        public Course $course,
        public Transaction $transaction,
    ) {}
}
