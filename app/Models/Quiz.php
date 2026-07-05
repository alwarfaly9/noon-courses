<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;
    protected $fillable = [
        'course_id',
        'course_section_id',
        'title',
        'description',
        'duration_minutes',
        'pass_mark',
    ];

    protected $with = ['questions.options']; // Auto-load questions

    public function section()
    {
        return $this->belongsTo(CourseSection::class, 'course_section_id');
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(QuizAttempt::class);
    }
}
