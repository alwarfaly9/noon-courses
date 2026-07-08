<?php

namespace App\Listeners;

use App\Events\CommentCreated;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendCommentNotification
{
    public function handle(CommentCreated $event): void
    {
        $comment = $event->comment;
        $lesson = $comment->lesson;
        if (!$lesson || !$lesson->course) return;

        $teacher = $lesson->course->teacher;
        if (!$teacher || $teacher->id === $comment->user_id) return;

        $prefs = UserNotificationPreference::forUser($teacher->id);
        if (!$prefs->allows('community_reply')) return;

        $commenter = $comment->user;

        NotificationService::send(
            $teacher,
            '💬 تعليق جديد على درسك',
            "علق {$commenter->name} على درس \"{$lesson->title}\": \"{$comment->comment}\"",
            'comment_created',
            [
                'lesson_id' => $lesson->id,
                'comment_id' => $comment->id,
                'action' => 'view_comment',
            ],
            category: 'community',
            priority: 'normal',
            actionUrl: "/courses/{$lesson->course->id}/lessons/{$lesson->id}",
            metadata: [
                'lesson_id' => $lesson->id,
                'comment_id' => $comment->id,
                'commenter_name' => $commenter->name,
            ],
        );
    }
}
