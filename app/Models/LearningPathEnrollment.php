<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LearningPathEnrollment extends Model
{
    use HasFactory;

    protected $fillable = [
        'learning_path_id',
        'user_id',
        'status',
        'progress_percentage',
        'enrolled_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'progress_percentage' => 'decimal:2',
            'enrolled_at'         => 'datetime',
            'completed_at'        => 'datetime',
        ];
    }

    public function learningPath()
    {
        return $this->belongsTo(LearningPath::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
