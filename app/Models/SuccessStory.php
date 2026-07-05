<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SuccessStory extends Model
{
    /** User-controlled fields only. is_approved/is_featured set via forceFill in admin code. */
    protected $fillable = [
        'user_id', 'title', 'body',
        'before_description', 'after_description',
        'image_url',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_approved' => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function scopePublished($query)
    {
        return $query->where('is_approved', true);
    }
}
