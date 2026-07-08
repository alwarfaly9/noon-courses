<?php

namespace App\Events;

use App\Models\Badge;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class AchievementUnlocked
{
    use Dispatchable;

    public function __construct(
        public User $user,
        public Badge $badge,
    ) {}
}
