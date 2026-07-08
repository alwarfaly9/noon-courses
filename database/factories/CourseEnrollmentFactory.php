<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseEnrollmentFactory extends Factory
{
    protected $model = CourseEnrollment::class;

    public function definition(): array
    {
        return [
            'student_id'          => User::factory(),
            'course_id'           => Course::factory(),
            'status'              => 'enrolled',
            'progress_percentage' => 0,
            'enrolled_at'         => now(),
        ];
    }
}
