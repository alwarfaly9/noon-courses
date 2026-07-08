<?php

namespace Database\Factories;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    protected $model = Certificate::class;

    public function definition(): array
    {
        return [
            'certificate_id' => $this->faker->unique()->regexify('CERT-[A-Z0-9]{6}'),
            'user_id'        => User::factory(),
            'course_id'      => Course::factory(),
            'issued_at'      => now(),
        ];
    }
}
