<?php

namespace App\Providers;

use App\Events\AchievementUnlocked;
use App\Events\AnnouncementCreated;
use App\Events\CommentCreated;
use App\Events\CommentReplied;
use App\Events\CourseApproved;
use App\Events\CourseCompleted;
use App\Events\CourseRejected;
use App\Events\EnrollmentCreated;
use App\Events\PaymentCompleted;
use App\Events\WithdrawalUpdated;
use App\Listeners\SendAchievementNotification;
use App\Listeners\SendAnnouncementNotification;
use App\Listeners\SendCommentNotification;
use App\Listeners\SendCommentReplyNotification;
use App\Listeners\SendCourseApprovedNotification;
use App\Listeners\SendCourseCompletedNotification;
use App\Listeners\SendCourseRejectedNotification;
use App\Listeners\SendEnrollmentNotification;
use App\Listeners\SendPaymentNotification;
use App\Listeners\SendWithdrawalUpdateNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        CourseCompleted::class => [SendCourseCompletedNotification::class],
        CourseApproved::class => [SendCourseApprovedNotification::class],
        CourseRejected::class => [SendCourseRejectedNotification::class],
        EnrollmentCreated::class => [SendEnrollmentNotification::class],
        AchievementUnlocked::class => [SendAchievementNotification::class],
        CommentCreated::class => [SendCommentNotification::class],
        CommentReplied::class => [SendCommentReplyNotification::class],
        PaymentCompleted::class => [SendPaymentNotification::class],
        WithdrawalUpdated::class => [SendWithdrawalUpdateNotification::class],
        AnnouncementCreated::class => [SendAnnouncementNotification::class],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
