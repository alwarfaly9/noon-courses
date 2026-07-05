<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Quiz;

class QuestionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'quiz_id' => Quiz::factory(),
            'content' => $this->faker->sentence . '?',
            'type' => 'multiple_choice',
        ];
    }

    public function configure()
    {
        return $this->afterCreating(function ($question) {
            // Create 4 options for the question
            QuestionOptionFactory::new()->count(3)->create([
                'question_id' => $question->id,
                'is_correct' => false,
            ]);
            // Create one correct option
            QuestionOptionFactory::new()->create([
                'question_id' => $question->id,
                'is_correct' => true,
            ]);
        });
    }
}
