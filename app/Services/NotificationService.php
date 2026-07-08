<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\NotificationAnalytics;
use App\Models\User;
use App\Services\FcmService;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Send a notification to specific user(s).
     * Saves to DB AND sends push via FCM.
     *
     * @param mixed $users User ID, User model, or array/collection of Users/IDs
     * @param string $title
     * @param string $message
     * @param string $type
     * @param mixed $data (optional) key-value/json data
     * @param string|null $category (e.g. 'system', 'course', 'payment', 'achievement', 'community')
     * @param string $priority ('low', 'normal', 'high')
     * @param string|null $actionUrl Deep-link URL for Flutter
     * @param array|null $metadata Additional structured metadata
     */
    public static function send(
        $users,
        $title,
        $message,
        $type = 'system',
        $data = null,
        ?string $category = null,
        string $priority = 'normal',
        ?string $actionUrl = null,
        ?array $metadata = null,
    ) {
        if (!is_iterable($users)) {
            $users = [$users];
        }

        $notifications = [];
        $now = now();
        $encodedData = $data ? json_encode($data) : null;
        $encodedMetadata = $metadata ? json_encode($metadata) : null;
        $userIds = [];
        $insertedIds = [];

        foreach ($users as $user) {
            $userId = $user instanceof User ? $user->id : $user;
            $userIds[] = $userId;
        }

        if (!empty($userIds)) {
            // Insert notifications and capture IDs
            foreach ($userIds as $userId) {
                $notifications[] = [
                    'user_id' => $userId,
                    'title' => $title,
                    'message' => $message,
                    'type' => $type,
                    'category' => $category,
                    'priority' => $priority,
                    'data' => $encodedData,
                    'action_url' => $actionUrl,
                    'metadata' => $encodedMetadata,
                    'is_read' => false,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Batch insert
            Notification::insert($notifications);

            // Fetch back inserted IDs for analytics tracking
            $insertedNotifications = Notification::where('created_at', $now)
                ->whereIn('user_id', $userIds)
                ->get(['id', 'user_id']);

            // Create sent analytics records
            $analytics = [];
            foreach ($insertedNotifications as $n) {
                $analytics[] = [
                    'notification_id' => $n->id,
                    'user_id' => $n->user_id,
                    'device_token_id' => null,
                    'event_type' => 'sent',
                    'metadata' => json_encode([
                        'type' => $type,
                        'category' => $category,
                        'priority' => $priority,
                    ]),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if (!empty($analytics)) {
                NotificationAnalytics::insert($analytics);
            }

            // Send push notification via FCM
            FcmService::sendToUsers($userIds, $title, $message, [
                'type' => $type,
                'category' => $category,
                'priority' => $priority,
                'action_url' => $actionUrl,
                'data' => $encodedData ?? '{}',
            ]);
        }
    }

    /**
     * Send notification to all admins.
     */
    public static function sendToAdmins($title, $message, $type = 'system', $data = null)
    {
        $admins = User::whereHas('roles', function($q) {
            $q->where('name', 'admin');
        })->get();

        self::send($admins, $title, $message, $type, $data);
    }

    /**
     * Record a notification opened event.
     */
    public static function markOpened(int $notificationId, int $userId): void
    {
        NotificationAnalytics::create([
            'notification_id' => $notificationId,
            'user_id' => $userId,
            'event_type' => 'opened',
            'metadata' => ['opened_at' => now()->toIso8601String()],
        ]);
    }
}
