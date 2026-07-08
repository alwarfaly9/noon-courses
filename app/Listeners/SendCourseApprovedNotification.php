<?php

namespace App\Listeners;

use App\Events\CourseApproved;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendCourseApprovedNotification
{
    public function handle(CourseApproved $event): void
    {
        $course = $event->course;
        $teacher = $course->teacher;

        if (!$teacher) return;

        $prefs = UserNotificationPreference::forUser($teacher->id);
        if (!$prefs->allows('announcement')) return;

        NotificationService::send(
            $teacher,
            '✅ تمت الموافقة على دورتك!',
            "دورتك \"{$course->title}\" تمت الموافقة عليها وهي الآن متاحة للطلاب.",
            'course_approved',
            [
                'course_id' => $course->id,
                'action' => 'manage_course',
            ],
            category: 'course',
            priority: 'high',
            actionUrl: "/teacher/courses/{$course->id}",
            metadata: ['course_id' => $course->id],
        );
    }
}
