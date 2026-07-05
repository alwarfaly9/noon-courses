<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CourseReview extends Model
{
    use HasFactory;

    /** User-controlled fields only. helpful_votes/is_featured/is_approved are set via forceFill in trusted code. */
    protected $fillable = [
        'course_id',
        'user_id',
        'rating',
        'review',
    ];

    protected function casts(): array
    {
        return [
            'rating'      => 'integer',
            'is_featured' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
