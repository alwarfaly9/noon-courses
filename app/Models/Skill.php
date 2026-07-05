<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $fillable = [
        'name', 'slug', 'description', 'icon', 'category', 'is_active', 'users_count',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'course_skills')
                    ->withPivot('level')
                    ->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_skills')
                    ->withPivot('level', 'earned_at', 'earned_via_course_id', 'earned_via_path_id')
                    ->withTimestamps();
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
