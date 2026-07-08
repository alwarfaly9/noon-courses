<?php

namespace Database\Factories;

use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class LearningPathEnrollmentFactory extends Factory
{
    protected $model = LearningPathEnrollment::class;

    public function definition(): array
    {
        return [
            'learning_path_id' => LearningPath::factory(),
            'user_id'          => User::factory(),
            'status'           => 'in_progress',
        ];
    }
}
