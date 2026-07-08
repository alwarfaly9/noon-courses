<?php

namespace App\Listeners;

use App\Events\CommentReplied;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendCommentReplyNotification
{
    public function handle(CommentReplied $event): void
    {
        $reply = $event->reply;
        $parent = $event->parent;

        $parentAuthor = $parent->user;
        if (!$parentAuthor || $parentAuthor->id === $reply->user_id) return;

        $prefs = UserNotificationPreference::forUser($parentAuthor->id);
        if (!$prefs->allows('community_reply')) return;

        $replier = $reply->user;
        $lesson = $reply->lesson;

        NotificationService::send(
            $parentAuthor,
            '💬 رد على تعليقك',
            "رد {$replier->name} على تعليقك: \"{$reply->comment}\"",
            'comment_replied',
            [
                'lesson_id' => $lesson?->id,
                'comment_id' => $reply->id,
                'action' => 'view_comment',
            ],
            category: 'community',
            priority: 'normal',
            actionUrl: $lesson ? "/courses/{$lesson->course_id}/lessons/{$lesson->id}" : null,
            metadata: [
                'reply_id' => $reply->id,
                'parent_id' => $parent->id,
                'replier_name' => $replier->name,
            ],
        );
    }
}
