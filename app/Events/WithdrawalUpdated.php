<?php

namespace App\Events;

use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Foundation\Events\Dispatchable;

class WithdrawalUpdated
{
    use Dispatchable;

    public function __construct(
        public WithdrawRequest $withdrawRequest,
        public User $teacher,
    ) {}
}
