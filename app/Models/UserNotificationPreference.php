<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserNotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'push_enabled', 'email_enabled', 'in_app_enabled',
        'streak_reminders', 'inactivity_reminders', 'achievement_alerts',
        'quiz_retry_reminders', 'path_reminders', 'community_replies',
        'teacher_announcements', 'recommended_content',
        'quiet_hour_start', 'quiet_hour_end',
    ];

    protected function casts(): array
    {
        return [
            'push_enabled'            => 'boolean',
            'email_enabled'           => 'boolean',
            'in_app_enabled'          => 'boolean',
            'streak_reminders'        => 'boolean',
            'inactivity_reminders'    => 'boolean',
            'achievement_alerts'      => 'boolean',
            'quiz_retry_reminders'    => 'boolean',
            'path_reminders'          => 'boolean',
            'community_replies'       => 'boolean',
            'teacher_announcements'   => 'boolean',
            'recommended_content'     => 'boolean',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** Returns (or creates with defaults) prefs for a user. */
    public static function forUser(int $userId): self
    {
        $prefs = self::firstOrCreate(['user_id' => $userId]);

        return $prefs->wasRecentlyCreated ? $prefs->fresh() : $prefs;
    }

    /**
     * Is it currently within the user's quiet hours?
     * All comparisons are done in UTC.
     */
    public function isQuietHourNow(): bool
    {
        $hour = (int) now()->format('H');
        $start = $this->quiet_hour_start;
        $end   = $this->quiet_hour_end;

        // Handles overnight windows (e.g. 23 → 7)
        if ($start > $end) {
            return $hour >= $start || $hour < $end;
        }
        return $hour >= $start && $hour < $end;
    }

    /**
     * Can we send a notification of a given trigger type right now?
     */
    public function allows(string $triggerType): bool
    {
        if ($this->isQuietHourNow()) return false;

        return match ($triggerType) {
            'streak_risk'      => $this->streak_reminders,
            'inactivity'       => $this->inactivity_reminders,
            'achievement'      => $this->achievement_alerts,
            'quiz_retry'       => $this->quiz_retry_reminders,
            'path_reminder'    => $this->path_reminders,
            'community_reply'  => $this->community_replies,
            'announcement'     => $this->teacher_announcements,
            'recommendation'   => $this->recommended_content,
            default            => $this->in_app_enabled,
        };
    }
}
