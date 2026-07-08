<?php

namespace App\Listeners;

use App\Events\AnnouncementCreated;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Log;

class SendAnnouncementNotification
{
    public function handle(AnnouncementCreated $event): void
    {
        $query = User::query()
            ->where('is_active', true)
            ->whereNull('deleted_at');

        if ($event->type === 'students') {
            $query->whereHas('roles', fn($q) => $q->where('name', 'student'));
        } elseif ($event->type === 'teachers') {
            $query->whereHas('roles', fn($q) => $q->where('name', 'teacher'));
        }

        $query->chunkById(100, function ($users) use ($event) {
            foreach ($users as $user) {
                $prefs = UserNotificationPreference::forUser($user->id);
                if (!$prefs->allows('announcement') && !$prefs->allows('in_app_enabled')) return;

                NotificationService::send(
                    $user,
                    $event->title,
                    $event->message,
                    'announcement',
                    $event->data ? (array) $event->data : ['action' => 'open'],
                    category: 'system',
                    priority: 'high',
                    actionUrl: $event->data['url'] ?? null,
                    metadata: ['announcement_type' => $event->type],
                );
            }
        });

        Log::info("Announcement sent to users: {$event->title}");
    }
}
