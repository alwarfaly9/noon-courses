<?php

namespace App\Events;

use App\Models\LessonComment;
use Illuminate\Foundation\Events\Dispatchable;

class CommentCreated
{
    use Dispatchable;

    public function __construct(
        public LessonComment $comment,
    ) {}
}
