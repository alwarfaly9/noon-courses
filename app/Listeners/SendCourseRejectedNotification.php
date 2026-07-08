<?php

namespace App\Listeners;

use App\Events\CourseRejected;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendCourseRejectedNotification
{
    public function handle(CourseRejected $event): void
    {
        $course = $event->course;
        $teacher = $course->teacher;

        if (!$teacher) return;

        $prefs = UserNotificationPreference::forUser($teacher->id);
        if (!$prefs->allows('announcement')) return;

        NotificationService::send(
            $teacher,
            '❌ لم تتم الموافقة على الدورة',
            "دورتك \"{$course->title}\" لم تتم الموافقتها. السبب: {$event->reason}",
            'course_rejected',
            [
                'course_id' => $course->id,
                'reason' => $event->reason,
                'action' => 'edit_course',
            ],
            category: 'course',
            priority: 'high',
            actionUrl: "/teacher/courses/{$course->id}/edit",
            metadata: ['course_id' => $course->id, 'reason' => $event->reason],
        );
    }
}
