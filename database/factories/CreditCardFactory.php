<?php

namespace Database\Factories;

use App\Models\CreditCard;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CreditCardFactory extends Factory
{
    protected $model = CreditCard::class;

    public function definition(): array
    {
        return [
            'serial_number' => $this->faker->unique()->lexify('CARD-????-????'),
            'value'         => $this->faker->randomElement([10, 25, 50, 100]),
            'status'        => 'active',
            'created_by'    => User::factory(),
            'expires_at'    => now()->addYear(),
        ];
    }
}
