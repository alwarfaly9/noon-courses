<?php

namespace Database\Factories;

use App\Models\CourseLesson;
use App\Models\Course;
use App\Models\CourseSection;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseLessonFactory extends Factory
{
    protected $model = CourseLesson::class;

    public function definition(): array
    {
        return [
            'course_id'   => Course::factory(),
            'section_id'  => CourseSection::factory(),
            'title'       => $this->faker->sentence(4),
            'description' => $this->faker->paragraph,
            'type'        => 'video',
            'duration'    => $this->faker->numberBetween(60, 1800),
            'order'       => $this->faker->numberBetween(1, 20),
            'is_preview'  => false,
        ];
    }
}
