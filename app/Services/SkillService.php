<?php

namespace App\Services;

use App\Models\Course;
use App\Models\LearningPath;
use App\Models\Skill;
use App\Models\User;
use App\Models\UserSkill;
use Illuminate\Support\Facades\DB;

class SkillService
{
    /**
     * Award all skills attached to a completed course to the student.
     */
    public function awardCourseSkills(User $user, Course $course, ?LearningPath $path = null): void
    {
        $skills = DB::table('course_skills')
            ->where('course_id', $course->id)
            ->get();

        foreach ($skills as $cs) {
            $existing = UserSkill::where('user_id', $user->id)
                ->where('skill_id', $cs->skill_id)
                ->first();

            // Upgrade level if the course gives a higher level
            $levels = ['beginner' => 1, 'intermediate' => 2, 'advanced' => 3];
            $newLevelInt = $levels[$cs->level] ?? 1;

            if ($existing) {
                $existingLevelInt = $levels[$existing->level] ?? 1;
                if ($newLevelInt > $existingLevelInt) {
                    $existing->update(['level' => $cs->level]);
                }
            } else {
                UserSkill::create([
                    'user_id'              => $user->id,
                    'skill_id'             => $cs->skill_id,
                    'level'                => $cs->level,
                    'earned_via_course_id' => $course->id,
                    'earned_via_path_id'   => $path?->id,
                    'earned_at'            => now(),
                ]);

                // Increment global skill usage counter
                Skill::where('id', $cs->skill_id)->increment('users_count');
            }
        }
    }

    /**
     * Award all unique skills from all courses in a completed path.
     */
    public function awardPathSkills(User $user, LearningPath $path): void
    {
        $courseIds = $path->courses()->pluck('courses.id')->toArray();

        foreach ($courseIds as $courseId) {
            $course = Course::find($courseId);
            if ($course) {
                $this->awardCourseSkills($user, $course, $path);
            }
        }
    }

    /**
     * Get skills recommended for a user based on what they haven't earned yet
     * that are attached to courses they haven't enrolled in,
     * ordered by trending (users_count DESC).
     */
    public function getRecommended(User $user, int $limit = 10): \Illuminate\Support\Collection
    {
        $earnedSkillIds = UserSkill::where('user_id', $user->id)->pluck('skill_id');

        return Skill::active()
            ->whereNotIn('id', $earnedSkillIds)
            ->orderByDesc('users_count')
            ->limit($limit)
            ->get();
    }

    /**
     * Trending skills globally (most users earning them recently).
     */
    public function getTrending(int $limit = 10): \Illuminate\Support\Collection
    {
        return Skill::active()
            ->orderByDesc('users_count')
            ->limit($limit)
            ->get();
    }
}
