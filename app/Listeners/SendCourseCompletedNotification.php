<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendCourseCompletedNotification
{
    public function handle(CourseCompleted $event): void
    {
        $user = $event->user;
        $course = $event->course;

        $prefs = UserNotificationPreference::forUser($user->id);
        if (!$prefs->allows('achievement')) return;

        NotificationService::send(
            $user,
            '🎉 أكملت الدورة بنجاح!',
            "تهانينا! لقد أكملت دورة \"{$course->title}\" بنجاح. استمر في التقدم!",
            'course_completed',
            [
                'course_id' => $course->id,
                'action' => 'open_course',
            ],
            category: 'course',
            priority: 'high',
            actionUrl: "/courses/{$course->id}",
            metadata: ['course_id' => $course->id],
        );
    }
}
