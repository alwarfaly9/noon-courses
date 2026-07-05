<?php

namespace App\Services;

use App\Models\CourseEnrollment;
use App\Models\NotificationLog;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Models\UserNotificationPreference;
use App\Models\UserStats;
use App\Models\LearningPathEnrollment;
use Illuminate\Support\Facades\DB;

/**
 * NotificationRulesService
 *
 * The behavioral notification engine.
 * Called by the `notifications:dispatch` Artisan command on a schedule.
 *
 * Design principles:
 * - Never spam: each trigger has a minimum cooldown period
 * - Respect quiet hours and user preferences
 * - Messages feel human, Arabic-first, non-pushy
 */
class NotificationRulesService
{
    // Cooldown constants (hours) — how often the same trigger can fire per user
    private const COOLDOWN = [
        'inactivity'    => 48,
        'streak_risk'   => 24,
        'quiz_retry'    => 72,
        'path_reminder' => 96,
        'recommendation' => 168, // once a week
    ];

    public function dispatchAll(): void
    {
        // Process in chunks to avoid memory issues at scale
        User::query()
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->whereHas('roles', fn($q) => $q->where('name', 'student'))
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $this->processUser($user);
                }
            });
    }

    public function processUser(User $user): void
    {
        $prefs = UserNotificationPreference::forUser($user->id);
        $stats = UserStats::firstOrCreate(['user_id' => $user->id]);

        // Order matters: more urgent checks first
        $this->checkStreakRisk($user, $prefs, $stats);
        $this->checkInactivity($user, $prefs, $stats);
        $this->checkQuizRetry($user, $prefs);
        $this->checkUnfinishedPath($user, $prefs);
    }

    // ── Rule: Streak at risk ───────────────────────────────────────────────────

    private function checkStreakRisk(User $user, UserNotificationPreference $prefs, UserStats $stats): void
    {
        if (!$prefs->allows('streak_risk')) return;
        if ($stats->current_streak_days < 2) return; // no streak to protect

        $lastActivity = $stats->last_activity_date;
        if (!$lastActivity) return;

        $hoursSince = $lastActivity->diffInHours(now());

        // Warn at 20h — still time to do a lesson today
        if ($hoursSince >= 20 && $hoursSince < 24) {
            if ($this->isOnCooldown($user->id, 'streak_risk')) return;

            $streak = $stats->current_streak_days;
            NotificationService::send(
                $user,
                "⚠️ سلسلتك في خطر!",
                "لديك سلسلة {$streak} يوم رائعة. أكمل درساً واحداً الآن لحمايتها.",
                'streak_risk',
                ['action' => 'continue_learning']
            );
            $this->logNotification($user->id, 'streak_risk');
        }
    }

    // ── Rule: Inactivity (no activity in 3 days) ──────────────────────────────

    private function checkInactivity(User $user, UserNotificationPreference $prefs, UserStats $stats): void
    {
        if (!$prefs->allows('inactivity')) return;

        $lastActivity = $stats->last_activity_date;
        if (!$lastActivity) {
            $lastActivity = $user->created_at->toDateString();
        }

        $daysSince = now()->diffInDays(\Carbon\Carbon::parse($lastActivity));

        if ($daysSince < 3) return;
        if ($this->isOnCooldown($user->id, 'inactivity')) return;

        $messages = [
            'نفتقدك في رحلة التعلم! 👋 لديك دورات تنتظر إكمالها.',
            'العودة للتعلم أسهل من البداية من الصفر. استكمل من حيث توقفت.',
            'المعرفة تنتظرك! دورة مكتملة هذا الأسبوع تستحق الجهد.',
        ];

        NotificationService::send(
            $user,
            'عد إلى رحلتك التعليمية 📚',
            $messages[$user->id % count($messages)],
            'inactivity',
            ['action' => 'continue_learning']
        );
        $this->logNotification($user->id, 'inactivity');
    }

    // ── Rule: Failed quiz eligible for retry ──────────────────────────────────

    private function checkQuizRetry(User $user, UserNotificationPreference $prefs): void
    {
        if (!$prefs->allows('quiz_retry')) return;

        // Find a recent failed attempt (last 14 days) with no subsequent pass
        $failedAttempt = QuizAttempt::where('user_id', $user->id)
            ->where('passed', false)
            ->where('completed_at', '>=', now()->subDays(14))
            ->whereDoesntHave('quiz', fn($q) =>
                $q->whereHas('attempts', fn($q2) =>
                    $q2->where('user_id', $user->id)->where('passed', true)
                )
            )
            ->with('quiz:id,title')
            ->latest('completed_at')
            ->first();

        if (!$failedAttempt) return;
        if ($this->isOnCooldown($user->id, 'quiz_retry', (string) $failedAttempt->quiz_id)) return;

        $title = $failedAttempt->quiz->title ?? 'الاختبار';
        NotificationService::send(
            $user,
            'جرب الاختبار مجدداً! 💪',
            "يمكنك تحسين نتيجتك في \"{$title}\". المعرفة تأتي بالتكرار.",
            'quiz_retry',
            ['quiz_id' => $failedAttempt->quiz_id, 'action' => 'open_quiz']
        );
        $this->logNotification($user->id, 'quiz_retry', (string) $failedAttempt->quiz_id);
    }

    // ── Rule: Enrolled path with no recent activity ───────────────────────────

    private function checkUnfinishedPath(User $user, UserNotificationPreference $prefs): void
    {
        if (!$prefs->allows('path_reminder')) return;

        $stale = LearningPathEnrollment::where('user_id', $user->id)
            ->where('progress_percentage', '>', 0)
            ->where('progress_percentage', '<', 100)
            ->whereNull('completed_at')
            ->where('updated_at', '<=', now()->subDays(5))
            ->with('learningPath:id,title')
            ->first();

        if (!$stale) return;
        if ($this->isOnCooldown($user->id, 'path_reminder', (string) $stale->learning_path_id)) return;

        $pathTitle = $stale->learningPath->title ?? 'المسار التعليمي';
        $pct = round($stale->progress_percentage);

        NotificationService::send(
            $user,
            'أنت في منتصف الطريق! 🏁',
            "لقد أكملت {$pct}% من \"{$pathTitle}\". استمر لإنهاء ما بدأت!",
            'path_reminder',
            ['path_id' => $stale->learning_path_id, 'action' => 'open_path']
        );
        $this->logNotification($user->id, 'path_reminder', (string) $stale->learning_path_id);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function isOnCooldown(int $userId, string $triggerType, string $refId = ''): bool
    {
        $cooldownHours = self::COOLDOWN[$triggerType] ?? 24;
        $since = now()->subHours($cooldownHours);

        return DB::table('notification_logs')
            ->where('user_id', $userId)
            ->where('trigger_type', $triggerType)
            ->when($refId !== '', fn($q) => $q->where('reference_id', $refId))
            ->where('sent_at', '>=', $since)
            ->exists();
    }

    private function logNotification(int $userId, string $triggerType, string $refId = ''): void
    {
        DB::table('notification_logs')->insert([
            'user_id'      => $userId,
            'trigger_type' => $triggerType,
            'reference_id' => $refId ?: null,
            'sent_at'      => now(),
        ]);
    }
}
