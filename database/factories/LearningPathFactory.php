<?php

namespace Database\Factories;

use App\Models\LearningPath;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningPathFactory extends Factory
{
    protected $model = LearningPath::class;

    public function definition(): array
    {
        return [
            'created_by'      => User::factory(),
            'title'           => $this->faker->sentence(4),
            'slug'            => $this->faker->unique()->slug(3),
            'description'     => $this->faker->paragraph,
            'difficulty_level' => 'beginner',
            'status'          => 'published',
        ];
    }
}
