<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseReview;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseReviewFactory extends Factory
{
    protected $model = CourseReview::class;

    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'user_id'   => User::factory(),
            'rating'    => $this->faker->numberBetween(1, 5),
            'review'    => $this->faker->paragraph,
        ];
    }
}
