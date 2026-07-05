<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Services\FcmService;

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
     */
    public static function send($users, $title, $message, $type = 'system', $data = null)
    {
        if (!is_iterable($users)) {
            $users = [$users];
        }

        $notifications = [];
        $now = now();
        $encodedData = $data ? json_encode($data) : null;
        $userIds = [];

        foreach ($users as $user) {
            $userId = $user instanceof User ? $user->id : $user;
            $userIds[] = $userId;
            
            $notifications[] = [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
                'data' => $encodedData,
                'is_read' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if (!empty($notifications)) {
            Notification::insert($notifications);
        }

        // Send push notification via FCM
        if (!empty($userIds)) {
            FcmService::sendToUsers($userIds, $title, $message, [
                'type' => $type,
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
}
