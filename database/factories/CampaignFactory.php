<?php

namespace Database\Factories;

use App\Models\Campaign;
use Illuminate\Database\Eloquent\Factories\Factory;

class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    public function definition(): array
    {
        return [
            'name'       => $this->faker->sentence(3),
            'slug'       => $this->faker->unique()->slug(2),
            'type'       => 'weekly_challenge',
            'goal_type'  => 'streak_days',
            'goal_value' => 5,
            'is_active'  => true,
            'starts_at'  => now()->subDay(),
            'ends_at'    => now()->addDays(30),
            'reward_xp'  => 100,
        ];
    }
}
