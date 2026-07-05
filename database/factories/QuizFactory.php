<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Quiz;
use App\Models\CourseSection;

class QuizFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_section_id' => CourseSection::factory(),
            'title' => $this->faker->sentence,
            'description' => $this->faker->paragraph,
            'course_id' => function (array $attributes) {
                return CourseSection::find($attributes['course_section_id'])->course_id;
            },
        ];
    }
}
