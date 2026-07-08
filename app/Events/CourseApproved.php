<?php

namespace App\Events;

use App\Models\Course;
use Illuminate\Foundation\Events\Dispatchable;

class CourseApproved
{
    use Dispatchable;

    public function __construct(
        public Course $course,
    ) {}
}
