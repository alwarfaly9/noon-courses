<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserDailyGoal extends Model
{
    protected $fillable = [
        'user_id', 'xp_target', 'lessons_target', 'streak_active',
        'goal_date', 'xp_earned_today', 'lessons_done_today',
    ];

    protected function casts(): array
    {
        return [
            'goal_date'      => 'date',
            'streak_active'  => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getXpProgressPercentAttribute(): float
    {
        return $this->xp_target > 0
            ? min(100.0, round(($this->xp_earned_today / $this->xp_target) * 100, 1))
            : 0.0;
    }

    public function getLessonsProgressPercentAttribute(): float
    {
        return $this->lessons_target > 0
            ? min(100.0, round(($this->lessons_done_today / $this->lessons_target) * 100, 1))
            : 0.0;
    }

    public function isXpGoalMet(): bool
    {
        return $this->xp_earned_today >= $this->xp_target;
    }

    public function isLessonsGoalMet(): bool
    {
        return $this->lessons_done_today >= $this->lessons_target;
    }

    /** Get or create today's goal record for a user. */
    public static function todayFor(int $userId): self
    {
        return self::firstOrCreate(
            ['user_id' => $userId, 'goal_date' => today()],
            ['xp_target' => 50, 'lessons_target' => 2]
        );
    }
}
