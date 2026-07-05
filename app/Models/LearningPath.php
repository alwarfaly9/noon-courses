<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LearningPath extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'created_by',
        'category_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'difficulty_level',
        'estimated_hours',
        'skill_tags',
        'status',
        'is_featured',
        'courses_count',
        'enrollments_count',
    ];

    protected function casts(): array
    {
        return [
            'skill_tags'    => 'array',
            'is_featured'   => 'boolean',
            'estimated_hours' => 'integer',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'learning_path_courses')
                    ->withPivot('order', 'is_required')
                    ->orderByPivot('order');
    }

    public function enrollments()
    {
        return $this->hasMany(LearningPathEnrollment::class);
    }

    public function enrolledUsers()
    {
        return $this->belongsToMany(User::class, 'learning_path_enrollments')
                    ->withPivot('status', 'progress_percentage', 'enrolled_at', 'completed_at')
                    ->withTimestamps();
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
