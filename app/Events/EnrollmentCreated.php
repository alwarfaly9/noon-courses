<?php

namespace App\Events;

use App\Models\Course;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class EnrollmentCreated
{
    use Dispatchable;

    public function __construct(
        public User $student,
        public Course $course,
    ) {}
}
