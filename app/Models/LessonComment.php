<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LessonComment extends Model
{
    use HasFactory, SoftDeletes;

    /** User-controlled fields. is_approved/is_pinned/reported_count set via forceFill in moderation code. */
    protected $fillable = [
        'lesson_id',
        'user_id',
        'parent_id',
        'content',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned'   => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function lesson()
    {
        return $this->belongsTo(CourseLesson::class, 'lesson_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(LessonComment::class, 'parent_id');
    }

    public function replies()
    {
        return $this->hasMany(LessonComment::class, 'parent_id')
                    ->where('is_approved', true)
                    ->orderBy('created_at');
    }

    public function reactions()
    {
        return $this->hasMany(CommentReaction::class, 'comment_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }
}
