<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalyticsDaily extends Model
{
    protected $table = 'analytics_daily';

    protected $fillable = [
        'date',
        'total_users',
        'new_users',
        'active_users',
        'enrollments',
        'lessons_completed',
        'quiz_attempts',
        'quizzes_passed',
        'revenue',
        'achievements_unlocked',
        'stories_created',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'revenue' => 'decimal:2',
        ];
    }
}
