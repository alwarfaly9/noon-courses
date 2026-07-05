<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserStats extends Model
{
    protected $fillable = [
        'user_id',
        'xp_total',
        'xp_this_week',
        'level',
        'current_streak_days',
        'longest_streak_days',
        'last_activity_date',
        'lessons_completed',
        'courses_completed',
        'quizzes_passed',
        'paths_completed',
    ];

    protected function casts(): array
    {
        return [
            'last_activity_date' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * XP thresholds per level (level => min XP).
     * Formula: roughly 500 * level^1.5 cumulative.
     */
    public static array $levels = [
        1  => 0,
        2  => 500,
        3  => 1500,
        4  => 3500,
        5  => 7000,
        6  => 13000,
        7  => 25000,
    ];

    public function getXpToNextLevelAttribute(): int
    {
        $next = self::$levels[$this->level + 1] ?? null;
        if ($next === null) return 0; // max level
        return $next - $this->xp_total;
    }

    public function getLevelProgressPercentAttribute(): float
    {
        $current = self::$levels[$this->level] ?? 0;
        $next    = self::$levels[$this->level + 1] ?? null;
        if ($next === null) return 100.0;
        $range = $next - $current;
        return round((($this->xp_total - $current) / $range) * 100, 1);
    }
}
