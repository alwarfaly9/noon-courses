<?php

namespace Database\Factories;

use App\Models\Badge;
use Illuminate\Database\Eloquent\Factories\Factory;

class BadgeFactory extends Factory
{
    protected $model = Badge::class;

    public function definition(): array
    {
        return [
            'name'            => $this->faker->word . ' Badge',
            'slug'            => $this->faker->unique()->slug(2),
            'description'     => $this->faker->sentence,
            'icon'            => 'fas fa-medal',
            'type'            => 'special',
            'condition_type'  => 'lessons_completed',
            'condition_value' => 1,
            'xp_reward'       => 0,
            'is_active'       => true,
        ];
    }
}
