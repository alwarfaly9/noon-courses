<?php

namespace Database\Factories;

use App\Models\Story;
use App\Models\User;
use App\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

class StoryFactory extends Factory
{
    protected $model = Story::class;

    public function definition(): array
    {
        return [
            'user_id'     => User::factory(),
            'course_id'   => null,
            'title'       => $this->faker->sentence(4),
            'body'        => $this->faker->paragraph,
            'media_type'  => 'image',
            'media_path'  => null,
            'media_url'   => null,
            'expires_at'  => null,
            'is_active'   => true,
            'views_count' => 0,
        ];
    }

    public function expired(): static
    {
        return $this->state(fn() => ['expires_at' => now()->subDay(), 'is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(fn() => ['is_active' => false]);
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn() => ['course_id' => $course->id]);
    }
}
