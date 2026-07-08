<?php

namespace Database\Factories;

use App\Models\SuccessStory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuccessStoryFactory extends Factory
{
    protected $model = SuccessStory::class;

    public function definition(): array
    {
        return [
            'user_id'            => User::factory(),
            'title'              => $this->faker->sentence(4),
            'body'               => $this->faker->paragraphs(3, true),
            'before_description' => $this->faker->sentence,
            'after_description'  => $this->faker->sentence,
            'is_approved'        => false,
        ];
    }
}
