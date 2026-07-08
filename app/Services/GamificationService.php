<?php

namespace App\Services;

use App\Events\AchievementUnlocked;
use App\Events\CourseCompleted;
use App\Models\Badge;
use App\Models\Course;
use App\Models\LearningPath;
use App\Models\User;
use App\Models\UserBadge;
use App\Models\UserStats;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GamificationService
{
    // ── XP Awards ────────────────────────────────────────────────────────────
    public const XP_LESSON_COMPLETE  = 15;
    public const XP_COURSE_COMPLETE  = 100;
    public const XP_QUIZ_PASS        = 25;
    public const XP_QUIZ_PERFECT     = 50;   // ≥90% score
    public const XP_PATH_COMPLETE    = 250;
    public const XP_DAILY_STREAK     = 5;
    public const XP_STREAK_7_DAYS    = 50;
    public const XP_STREAK_30_DAYS   = 200;
    public const XP_FIRST_COMMENT    = 5;

    // ── Level thresholds ─────────────────────────────────────────────────────
    private const LEVEL_MAP = [1=>0, 2=>500, 3=>1500, 4=>3500, 5=>7000, 6=>13000, 7=>25000];

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Award XP and update streak/level. All state mutations are atomic.
     */
    public function awardXp(User $user, int $xp, string $reason = ''): UserStats
    {
        return DB::transaction(function () use ($user, $xp, $reason) {
            $stats = UserStats::firstOrCreate(
                ['user_id' => $user->id],
                ['xp_total' => 0, 'xp_this_week' => 0, 'level' => 1]
            );

            $stats->increment('xp_total', $xp);
            $stats->increment('xp_this_week', $xp);

            // Recalculate level
            $newLevel = 1;
            foreach (self::LEVEL_MAP as $lvl => $threshold) {
                if ($stats->xp_total >= $threshold) $newLevel = $lvl;
            }

            if ($newLevel !== $stats->level) {
                $stats->update(['level' => $newLevel]);
                Log::info("[Gamification] User #{$user->id} leveled up to {$newLevel}");
            }

            Log::info("[Gamification] +{$xp} XP to user #{$user->id} ({$reason})");

            return $stats->fresh();
        });
    }

    /**
     * Update daily streak. Call on every meaningful user action.
     */
    public function updateStreak(User $user): UserStats
    {
        $stats = UserStats::firstOrCreate(['user_id' => $user->id]);
        $today = now()->toDateString();

        if ($stats->last_activity_date?->toDateString() === $today) {
            return $stats; // Already recorded today
        }

        $isConsecutive = $stats->last_activity_date?->toDateString() === now()->subDay()->toDateString();

        $newStreak = $isConsecutive ? $stats->current_streak_days + 1 : 1;
        $longest   = max($stats->longest_streak_days, $newStreak);

        $stats->update([
            'current_streak_days' => $newStreak,
            'longest_streak_days' => $longest,
            'last_activity_date'  => $today,
        ]);

        // Award streak XP
        $this->awardXp($user, self::XP_DAILY_STREAK, 'daily_streak');

        if ($newStreak === 7)  $this->awardXp($user, self::XP_STREAK_7_DAYS, '7_day_streak');
        if ($newStreak === 30) $this->awardXp($user, self::XP_STREAK_30_DAYS, '30_day_streak');

        // Check streak badges
        $this->checkAndAwardBadges($user);

        return $stats->fresh();
    }

    // ── Event Hooks ───────────────────────────────────────────────────────────

    public function onLessonCompleted(User $user): void
    {
        $this->updateStreak($user);
        $stats = $this->awardXp($user, self::XP_LESSON_COMPLETE, 'lesson_completed');
        $stats->increment('lessons_completed');
        $this->checkAndAwardBadges($user);
    }

    public function onCourseCompleted(User $user, Course $course): void
    {
        $stats = $this->awardXp($user, self::XP_COURSE_COMPLETE, 'course_completed');
        $stats->increment('courses_completed');
        $this->checkAndAwardBadges($user);

        // Auto-award skills for the course
        app(SkillService::class)->awardCourseSkills($user, $course);

        // Dispatch event for notification pipeline
        CourseCompleted::dispatch($user, $course);
    }

    public function onQuizPassed(User $user, float $scorePercentage): void
    {
        $xp = $scorePercentage >= 90 ? self::XP_QUIZ_PERFECT : self::XP_QUIZ_PASS;
        $stats = $this->awardXp($user, $xp, 'quiz_passed');
        $stats->increment('quizzes_passed');
        $this->checkAndAwardBadges($user);
    }

    public function onPathCompleted(User $user, LearningPath $path): void
    {
        $stats = $this->awardXp($user, self::XP_PATH_COMPLETE, 'path_completed');
        $stats->increment('paths_completed');
        $this->checkAndAwardBadges($user);

        // Notify user
        app(NotificationService::class)->send(
            $user,
            '🎉 أتممت مسار التعلم!',
            "تهانينا! لقد أتممت مسار \"{$path->title}\"",
            'achievement'
        );
    }

    public function onFirstComment(User $user): void
    {
        $this->awardXp($user, self::XP_FIRST_COMMENT, 'first_comment');
    }

    // ── Badge Engine ─────────────────────────────────────────────────────────

    /**
     * Evaluate all active badges against a user's current stats and award any new ones.
     */
    public function checkAndAwardBadges(User $user): void
    {
        $stats = UserStats::where('user_id', $user->id)->first();
        if (!$stats) return;

        $earnedIds = UserBadge::where('user_id', $user->id)->pluck('badge_id')->toArray();

        // Cache active badges to prevent querying per check
        $candidates = \Illuminate\Support\Facades\Cache::remember('gamification_active_badges', 3600, function () {
            return Badge::where('is_active', true)->get();
        })->whereNotIn('id', $earnedIds);

        foreach ($candidates as $badge) {
            if ($this->evaluateBadge($badge, $stats)) {
                $this->awardBadge($user, $badge);
            }
        }
    }

    private function evaluateBadge(Badge $badge, UserStats $stats): bool
    {
        return match ($badge->condition_type) {
            'lessons_completed'       => $stats->lessons_completed        >= $badge->condition_value,
            'courses_completed'       => $stats->courses_completed        >= $badge->condition_value,
            'quizzes_passed'          => $stats->quizzes_passed           >= $badge->condition_value,
            'paths_completed'         => $stats->paths_completed          >= $badge->condition_value,
            'streak_days'             => $stats->current_streak_days      >= $badge->condition_value,
            'longest_streak'          => $stats->longest_streak_days      >= $badge->condition_value,
            'xp_total'                => $stats->xp_total                 >= $badge->condition_value,
            'level'                   => $stats->level                    >= $badge->condition_value,
            'comments_posted'         => $stats->comments_posted          >= $badge->condition_value,
            'helpful_votes_received'  => $stats->helpful_votes_received   >= $badge->condition_value,
            default                   => false,
        };
    }

    private function awardBadge(User $user, Badge $badge): void
    {
        UserBadge::create([
            'user_id'   => $user->id,
            'badge_id'  => $badge->id,
            'earned_at' => now(),
        ]);

        if ($badge->xp_reward > 0) {
            $this->awardXp($user, $badge->xp_reward, "badge:{$badge->slug}");
        }

        // Dispatch event for notification pipeline
        AchievementUnlocked::dispatch($user, $badge);

        Log::info("[Gamification] Badge '{$badge->slug}' awarded to user #{$user->id}");
    }

    // ── Leaderboard ───────────────────────────────────────────────────────────

    public function getLeaderboard(int $limit = 20): \Illuminate\Support\Collection
    {
        return UserStats::with('user:id,name,avatar')
            ->orderByDesc('xp_total')
            ->limit($limit)
            ->get()
            ->map(fn($stat, $i) => [
                'rank'   => $i + 1,
                'user'   => $stat->user,
                'xp'     => $stat->xp_total,
                'level'  => $stat->level,
                'streak' => $stat->current_streak_days,
            ]);
    }
}
