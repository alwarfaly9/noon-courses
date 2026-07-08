<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class AnnouncementCreated
{
    use Dispatchable;

    public function __construct(
        public string $title,
        public string $message,
        public string $type = 'system',
        public mixed $data = null,
    ) {}
}
