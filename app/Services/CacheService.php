<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CacheService — Centralised Redis cache management for NOON Platform.
 *
 * All cache keys are documented here. Tags are used where the store supports
 * them (Redis). When the store is 'database' or 'file', tag operations are
 * silently skipped via the graceful helpers below.
 *
 * KEY INVENTORY
 * ─────────────────────────────────────────────────────────────────────────────
 * noon:course:list:{hash}          — Paginated public course list (5 min)
 * noon:course:{id}                 — Single course detail (10 min)
 * noon:categories                  — All categories (30 min)
 * noon:banners                     — Promotional banners (15 min)
 * noon:learning_paths:list         — Public LP list (10 min)
 * noon:learning_path:{slug}        — LP detail (10 min)
 * noon:success_stories:{page}      — Approved stories paginated (5 min)
 * noon:instructor:{id}             — Instructor profile (15 min)
 * noon:gamification:leaderboard    — Top 20 leaderboard (2 min)
 * noon:reviews:{courseId}:{page}   — Course reviews paginated (5 min)
 * noon:dashboard:{userId}          — Personalised dashboard (3 min)
 * noon:analytics:{userId}          — Learning analytics (5 min)
 * ─────────────────────────────────────────────────────────────────────────────
 */
class CacheService
{
    // ── TTL constants (seconds) ───────────────────────────────────────────────

    public const TTL_VERY_SHORT  = 60;       //  1 min  — real-time data
    public const TTL_SHORT       = 180;      //  3 min  — personalised
    public const TTL_MEDIUM      = 300;      //  5 min  — lists
    public const TTL_STANDARD    = 600;      // 10 min  — detail pages
    public const TTL_LONG        = 900;      // 15 min  — profiles / banners
    public const TTL_VERY_LONG   = 1800;     // 30 min  — categories (rarely change)
    public const TTL_LEADERBOARD = 120;      //  2 min  — gamification board

    // ── Key builders ──────────────────────────────────────────────────────────

    public static function courseListKey(array $filters): string
    {
        return 'noon:course:list:' . md5(serialize($filters));
    }

    public static function courseKey(int $id): string
    {
        return "noon:course:{$id}";
    }

    public static function categoriesKey(): string
    {
        return 'noon:categories';
    }

    public static function bannersKey(): string
    {
        return 'noon:banners';
    }

    public static function learningPathsKey(): string
    {
        return 'noon:learning_paths:list';
    }

    public static function learningPathKey(string $slug): string
    {
        return "noon:learning_path:{$slug}";
    }

    public static function storiesKey(int $page = 1, bool $featured = false): string
    {
        $suffix = $featured ? 'featured' : 'all';
        return "noon:success_stories:{$suffix}:{$page}";
    }

    public static function instructorKey(int $userId): string
    {
        return "noon:instructor:{$userId}";
    }

    public static function reviewsKey(int $courseId, int $page = 1): string
    {
        return "noon:reviews:{$courseId}:{$page}";
    }

    public static function dashboardKey(int $userId): string
    {
        return "noon:dashboard:{$userId}";
    }

    public static function analyticsKey(int $userId): string
    {
        return "noon:analytics:{$userId}";
    }

    // ── Invalidation helpers ──────────────────────────────────────────────────

    /**
     * Invalidate all cache related to a specific course.
     * Call this when a course is updated, approved, or deleted.
     */
    public static function invalidateCourse(int $courseId): void
    {
        Cache::forget(static::courseKey($courseId));
        // Reviews for all pages
        for ($page = 1; $page <= 20; $page++) {
            Cache::forget(static::reviewsKey($courseId, $page));
        }
        // Course list caches cannot be invalidated by key pattern without Redis SCAN.
        // They will expire naturally (TTL_MEDIUM).
        Log::debug("[Cache] Invalidated course:{$courseId}");
    }

    /**
     * Invalidate dashboard + analytics for a user.
     * Call this after enrollment, lesson completion, or quiz submission.
     */
    public static function invalidateUser(int $userId): void
    {
        Cache::forget(static::dashboardKey($userId));
        Cache::forget(static::analyticsKey($userId));
        Log::debug("[Cache] Invalidated user:{$userId} dashboard+analytics");
    }

    /**
     * Invalidate instructor profile.
     * Call this when an instructor updates their profile or a course changes.
     */
    public static function invalidateInstructor(int $userId): void
    {
        Cache::forget(static::instructorKey($userId));
        Log::debug("[Cache] Invalidated instructor:{$userId}");
    }

    /**
     * Invalidate all story caches (on new approval/feature toggle).
     */
    public static function invalidateStories(): void
    {
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget(static::storiesKey($page, false));
            Cache::forget(static::storiesKey($page, true));
        }
        Log::debug('[Cache] Invalidated all story pages');
    }

    /**
     * Invalidate categories and banners (rarely needed).
     */
    public static function invalidateStatic(): void
    {
        Cache::forget(static::categoriesKey());
        Cache::forget(static::bannersKey());
        Cache::forget(static::learningPathsKey());
    }

    // ── Generic remember helpers (with graceful error fallback) ───────────────

    /**
     * Cache::remember() with error resilience.
     * If Redis is down, executes the callback directly without caching.
     */
    public static function remember(string $key, int $ttl, callable $callback): mixed
    {
        try {
            return Cache::remember($key, $ttl, $callback);
        } catch (\Throwable $e) {
            Log::warning("[Cache] Cache miss (store error): {$e->getMessage()}", ['key' => $key]);
            return $callback();
        }
    }
}
