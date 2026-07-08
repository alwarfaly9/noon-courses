<?php

namespace App\Listeners;

use App\Events\PaymentCompleted;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendPaymentNotification
{
    public function handle(PaymentCompleted $event): void
    {
        $student = $event->student;
        $course = $event->course;
        $transaction = $event->transaction;

        // Notify student
        $prefs = UserNotificationPreference::forUser($student->id);
        if ($prefs->allows('recommendation')) {
            NotificationService::send(
                $student,
                '✅ تم تأكيد الدفع',
                "تم تأكيد دفعتك لدورة \"{$course->title}\" بقيمة {$transaction->amount} {$transaction->currency}. يمكنك الآن الوصول الكامل للدورة.",
                'payment_completed',
                [
                    'transaction_id' => $transaction->id,
                    'action' => 'open_course',
                ],
                category: 'payment',
                priority: 'high',
                actionUrl: "/courses/{$course->id}",
                metadata: [
                    'transaction_id' => $transaction->id,
                    'amount' => $transaction->amount,
                    'currency' => $transaction->currency,
                ],
            );
        }

        // Notify teacher
        $teacher = $course->teacher;
        if ($teacher) {
            $teacherPrefs = UserNotificationPreference::forUser($teacher->id);
            if ($teacherPrefs->allows('announcement')) {
                NotificationService::send(
                    $teacher,
                    '💰 عملية شراء جديدة',
                    "قام {$student->name} بشراء دورتك \"{$course->title}\" بقيمة {$transaction->amount} {$transaction->currency}.",
                    'payment_completed',
                    [
                        'transaction_id' => $transaction->id,
                        'student_id' => $student->id,
                        'action' => 'view_sales',
                    ],
                    category: 'payment',
                    priority: 'high',
                    actionUrl: "/teacher/sales",
                    metadata: [
                        'transaction_id' => $transaction->id,
                        'student_id' => $student->id,
                        'amount' => $transaction->amount,
                    ],
                );
            }
        }
    }
}
