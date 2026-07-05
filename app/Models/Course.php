<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'category_id',
        'title',
        'slug',
        'description',
        'short_description',
        'price',
        'discount_price',
        'image',
        'video_intro',
        'tags',
        'requirements',
        'what_you_will_learn',
        'level',
        'language',
        'duration',
        'lectures_count',
        'students_count',
        'rating',
        'reviews_count',
        'status',
        'rejection_reason',
        'is_featured',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'tags' => 'array',
            'requirements' => 'array',
            'what_you_will_learn' => 'array',
            'price' => 'decimal:2',
            'discount_price' => 'decimal:2',
            'rating' => 'decimal:2',
            'is_featured' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'course_enrollments', 'course_id', 'student_id')
                    ->withPivot('status', 'progress_percentage', 'enrolled_at', 'completed_at')
                    ->withTimestamps();
    }

    public function enrollments()
    {
        return $this->hasMany(CourseEnrollment::class);
    }

    public function sections()
    {
        return $this->hasMany(CourseSection::class)->orderBy('order');
    }

    public function lessons()
    {
        return $this->hasMany(CourseLesson::class)->orderBy('order');
    }

    public function reviews()
    {
        return $this->hasMany(CourseReview::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Helper Methods
    public function getFinalPrice()
    {
        return $this->discount_price ?? $this->price;
    }
}
