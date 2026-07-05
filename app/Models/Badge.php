<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Badge extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'type',
        'condition_type',
        'condition_value',
        'xp_reward',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function userBadges()
    {
        return $this->hasMany(UserBadge::class);
    }
}
