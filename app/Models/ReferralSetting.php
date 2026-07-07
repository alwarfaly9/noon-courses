<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReferralSetting extends Model
{
    protected $fillable = [
        'reward_amount', 'reward_type', 'xp_reward',
        'max_rewards_per_user', 'max_rewards_total', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'reward_amount' => 'decimal:2',
            'is_active'     => 'boolean',
        ];
    }

    public static function current(): self
    {
        return self::where('is_active', true)->latest()->first()
            ?? new self(['reward_amount' => 5, 'reward_type' => 'wallet', 'xp_reward' => 0, 'is_active' => true]);
    }
}
