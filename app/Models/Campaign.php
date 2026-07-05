<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    protected $fillable = [
        'name', 'slug', 'type', 'description', 'banner_image_url',
        'reward_xp', 'reward_badge_id', 'goal_type', 'goal_value',
        'is_active', 'starts_at', 'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'starts_at'  => 'datetime',
            'ends_at'    => 'datetime',
            'reward_xp'  => 'integer',
            'goal_value' => 'integer',
        ];
    }

    public function badge()
    {
        return $this->belongsTo(Badge::class, 'reward_badge_id');
    }

    public function participations()
    {
        return $this->hasMany(CampaignParticipation::class);
    }

    /** Active campaigns that have started and not yet ended */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }
}
