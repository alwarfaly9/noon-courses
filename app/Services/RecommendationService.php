<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearningPath;
use App\Models\Skill;
use App\Models\UserSkill;
use App\Models\UserStats;
use Illuminate\Support\Collection;

/**
 * RecommendationService
 *
 * All recommendations are computed on-demand and cached at the controller layer.
 * Scoring model: collaborative signals (category/skill overlap) + popularity (students_count, rating)
 * weighted together. Completed and enrolled courses are always excluded.
 */
class RecommendationService
{
    /**
     * Recommend courses for a user.
     * Signal priority:
     * 1. Courses in categories the user is already enrolled in
     * 2. Courses with skills the user wants to earn (recommended skills)
     * 3. Highly-rated, published, free/affordable courses the user hasn't seen
     */
    public function recommendCourses(int $userId, int $limit = 6): Collection
    {
        $enrolledCourseIds = CourseEnrollment::where('student_id', $userId)
            ->pluck('course_id');

        // Merge enrolled-course categories with explicitly declared interests
        $enrolledCategories = Course::whereIn('id', $enrolledCourseIds)
            ->pluck('category_id')
            ->unique();

        $interestCategories = \Illuminate\Support\Facades\DB::table('user_interests')
            ->where('user_id', $userId)
            ->pluck('category_id');

        $relevantCategories = $enrolledCategories->merge($interestCategories)->unique();

        // Also pull in skill-goal aligned courses
        $goalSkillIds = \Illuminate\Support\Facades\DB::table('user_skill_goals')
            ->where('user_id', $userId)
            ->pluck('skill_id');

        $skillAlignedCourseIds = $goalSkillIds->isEmpty()
            ? collect()
            : \Illuminate\Support\Facades\DB::table('course_skills')
                ->whereIn('skill_id', $goalSkillIds)
                ->pluck('course_id');

        $catClause = $relevantCategories->isEmpty() ? '0' : $relevantCategories->implode(',');
        $skillClause = $skillAlignedCourseIds->isEmpty() ? '0' : $skillAlignedCourseIds->implode(',');

        return Course::with(['teacher:id,name,avatar', 'category:id,name'])
            ->where('status', 'published')
            ->whereNotIn('id', $enrolledCourseIds)
            ->selectRaw("
                courses.*,
                (
                    CASE WHEN category_id IN ({$catClause}) THEN 30 ELSE 0 END
                    + CASE WHEN id IN ({$skillClause}) THEN 25 ELSE 0 END
                    + (rating * 4)
                    + LEAST(students_count / 100, 20)
                ) as relevance_score
            ")
            ->orderByDesc('relevance_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Recommend learning paths: published paths the user is NOT enrolled in,
     * prioritizing paths whose skill_tags overlap with user's existing skills.
     */
    public function recommendPaths(int $userId, int $limit = 4): Collection
    {
        $enrolledPathIds = \App\Models\LearningPathEnrollment::where('user_id', $userId)
            ->pluck('learning_path_id');

        $earnedSkillNames = UserSkill::where('user_id', $userId)
            ->with('skill:id,name')
            ->get()
            ->pluck('skill.name')
            ->filter()
            ->map(fn($n) => strtolower($n));

        $paths = LearningPath::where('status', 'published')
            ->whereNotIn('id', $enrolledPathIds)
            ->with(['category:id,name'])
            ->withCount('enrollments')
            ->get();

        // Score each path by skill_tags overlap
        return $paths
            ->map(function ($path) use ($earnedSkillNames) {
                $tags   = collect($path->skill_tags)->map(fn($t) => strtolower($t));
                $overlap = $tags->intersect($earnedSkillNames)->count();
                $path->relevance_score = ($overlap * 15) + min($path->enrollments_count / 10, 20);
                return $path;
            })
            ->sortByDesc('relevance_score')
            ->take($limit)
            ->values();
    }

    /**
     * Recommend skills the user has not yet earned, prioritised by
     * how popular those skills are among enrolled users.
     */
    public function recommendSkills(int $userId, int $limit = 8): Collection
    {
        $earnedSkillIds = UserSkill::where('user_id', $userId)->pluck('skill_id');

        return Skill::where('is_active', true)
            ->whereNotIn('id', $earnedSkillIds)
            ->orderByDesc('users_count')
            ->limit($limit)
            ->get(['id', 'name', 'category', 'users_count']);
    }

    /**
     * Courses the user has started but not finished (< 100% progress).
     */
    public function continueLearning(int $userId, int $limit = 3): Collection
    {
        return CourseEnrollment::where('student_id', $userId)
            ->where('progress_percentage', '>', 0)
            ->where('progress_percentage', '<', 100)
            ->whereNull('completed_at')
            ->with([
                'course:id,title,slug,image,level,category_id',
                'course.category:id,name',
            ])
            ->orderByDesc('updated_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Weekly activity array (7 days, index 0 = today).
     * Returns count of lesson completions per day.
     */
    public function weeklyActivity(int $userId): array
    {
        $rows = \Illuminate\Support\Facades\DB::table('lesson_completions')
            ->where('user_id', $userId)
            ->where('completed_at', '>=', now()->subDays(6)->startOfDay())
            ->selectRaw('DATE(completed_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->pluck('count', 'day');

        $result = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i)->format('Y-m-d');
            $result[] = (int) ($rows[$date] ?? 0);
        }
        return $result;
    }
}
