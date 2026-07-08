<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition(): array
    {
        return [
            'code'           => $this->faker->unique()->lexify('COUPON-????'),
            'name'           => $this->faker->word,
            'discount_type'  => 'percentage',
            'discount_value' => 10,
            'usage_limit'    => 100,
            'used_count'     => 0,
            'is_active'      => true,
            'expires_at'     => now()->addDays(30),
        ];
    }
}
