<?php

namespace App\Listeners;

use App\Events\AchievementUnlocked;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendAchievementNotification
{
    public function handle(AchievementUnlocked $event): void
    {
        $user = $event->user;
        $badge = $event->badge;

        $prefs = UserNotificationPreference::forUser($user->id);
        if (!$prefs->allows('achievement')) return;

        NotificationService::send(
            $user,
            '🏆 إنجاز جديد!',
            "لقد حصلت على شارة \"{$badge->name}\"! استمر في التقدم لكشف المزيد من الإنجازات.",
            'achievement_unlocked',
            [
                'badge_id' => $badge->id,
                'action' => 'view_achievements',
            ],
            category: 'achievement',
            priority: 'high',
            actionUrl: "/profile/achievements",
            metadata: ['badge_id' => $badge->id, 'badge_name' => $badge->name],
        );
    }
}
