<?php

namespace App\Listeners;

use App\Events\EnrollmentCreated;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendEnrollmentNotification
{
    public function handle(EnrollmentCreated $event): void
    {
        $student = $event->student;
        $course = $event->course;
        $teacher = $course->teacher;

        // Notify student
        $prefs = UserNotificationPreference::forUser($student->id);
        if ($prefs->allows('recommendation')) {
            NotificationService::send(
                $student,
                '📚 مرحباً بك في الدورة!',
                "لقد تم تسجيلك في دورة \"{$course->title}\". ابدأ رحلة التعلم الآن!",
                'enrollment_created',
                [
                    'course_id' => $course->id,
                    'action' => 'open_course',
                ],
                category: 'course',
                priority: 'normal',
                actionUrl: "/courses/{$course->id}",
                metadata: ['course_id' => $course->id, 'role' => 'student'],
            );
        }

        // Notify teacher
        if ($teacher) {
            $teacherPrefs = UserNotificationPreference::forUser($teacher->id);
            if ($teacherPrefs->allows('announcement')) {
                NotificationService::send(
                    $teacher,
                    '📋 طالب جديد في دورتك',
                    "انضم الطالب {$student->name} إلى دورتك \"{$course->title}\".",
                    'enrollment_created',
                    [
                        'course_id' => $course->id,
                        'student_id' => $student->id,
                        'action' => 'manage_course',
                    ],
                    category: 'course',
                    priority: 'normal',
                    actionUrl: "/teacher/courses/{$course->id}",
                    metadata: ['course_id' => $course->id, 'student_id' => $student->id, 'role' => 'teacher'],
                );
            }
        }
    }
}
