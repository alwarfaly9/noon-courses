<?php

namespace App\Events;

use App\Models\LessonComment;
use Illuminate\Foundation\Events\Dispatchable;

class CommentReplied
{
    use Dispatchable;

    public function __construct(
        public LessonComment $reply,
        public LessonComment $parent,
    ) {}
}
