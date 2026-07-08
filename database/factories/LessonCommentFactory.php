<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use App\Models\LessonComment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonCommentFactory extends Factory
{
    protected $model = LessonComment::class;

    public function definition(): array
    {
        return [
            'lesson_id' => CourseLesson::factory(),
            'user_id'   => User::factory(),
            'content'   => $this->faker->paragraph,
            'is_approved' => true,
        ];
    }
}
