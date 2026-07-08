<?php

namespace App\Listeners;

use App\Events\WithdrawalUpdated;
use App\Models\UserNotificationPreference;
use App\Services\NotificationService;

class SendWithdrawalUpdateNotification
{
    public function handle(WithdrawalUpdated $event): void
    {
        $request = $event->withdrawRequest;
        $teacher = $event->teacher;

        $prefs = UserNotificationPreference::forUser($teacher->id);
        if (!$prefs->allows('announcement')) return;

        $statusLabels = [
            'approved' => 'تمت الموافقة',
            'rejected' => 'تم الرفض',
            'completed' => 'تم الصرف',
            'cancelled' => 'تم الإلغاء',
        ];

        $label = $statusLabels[$request->status] ?? $request->status;

        NotificationService::send(
            $teacher,
            "💰 تحديث حالة السحب: {$label}",
            "تم تحديث حالة طلب السحب بقيمة {$request->amount} {$request->currency} إلى: {$label}.",
            'withdrawal_updated',
            [
                'withdraw_id' => $request->id,
                'status' => $request->status,
                'action' => 'view_withdrawals',
            ],
            category: 'payment',
            priority: 'high',
            actionUrl: "/teacher/withdrawals",
            metadata: [
                'withdraw_id' => $request->id,
                'status' => $request->status,
                'amount' => $request->amount,
            ],
        );
    }
}
