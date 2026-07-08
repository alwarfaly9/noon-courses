<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'transaction_number' => 'TRX-' . strtoupper($this->faker->unique()->lexify('????????')),
            'user_id' => User::factory(),
            'course_id' => Course::factory(),
            'type' => 'purchase',
            'status' => 'completed',
            'amount' => 50.00,
            'platform_commission' => 5.00,
            'instructor_earnings' => 45.00,
            'completed_at' => now(),
        ];
    }
}
