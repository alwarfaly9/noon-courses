<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WithdrawRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

class WithdrawRequestFactory extends Factory
{
    protected $model = WithdrawRequest::class;

    public function definition(): array
    {
        return [
            'user_id'        => User::factory(),
            'amount'         => $this->faker->randomFloat(2, 10, 500),
            'bank_name'      => $this->faker->company,
            'account_name'   => $this->faker->name,
            'account_number' => $this->faker->bankAccountNumber,
            'status'         => 'pending',
        ];
    }
}
