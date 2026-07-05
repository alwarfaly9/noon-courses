<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CampaignParticipation extends Model
{
    protected $fillable = [
        'campaign_id', 'user_id', 'progress', 'completed', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'completed'    => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
