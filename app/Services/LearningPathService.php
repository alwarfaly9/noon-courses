<?php

namespace App\Services;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\LearningPath;
use App\Models\LearningPathEnrollment;
use App\Models\User;
use App\Services\GamificationService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LearningPathService
{
    /**
     * Enroll a user in a learning path.
     * Also auto-enroll in all required courses (free enrollment only).
     *
     * @throws \Exception
     */
    public function enroll(User $user, LearningPath $path): LearningPathEnrollment
    {
        if ($path->status !== 'published') {
            throw new \Exception('مسار التعلم غير متاح للتسجيل');
        }

        $existing = LearningPathEnrollment::where('learning_path_id', $path->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existing) {
            throw new \Exception('أنت مسجل بالفعل في هذا المسار');
        }

        return DB::transaction(function () use ($user, $path) {
            $enrollment = LearningPathEnrollment::create([
                'learning_path_id' => $path->id,
                'user_id'          => $user->id,
                'status'           => 'in_progress',
                'enrolled_at'      => now(),
            ]);

            // Increment counter
            $path->increment('enrollments_count');

            return $enrollment;
        });
    }

    /**
     * Recalculate path progress for a user based on enrolled courses' completion.
     */
    public function recalculateProgress(User $user, LearningPath $path): float
    {
        $enrollment = LearningPathEnrollment::where('learning_path_id', $path->id)
            ->where('user_id', $user->id)
            ->first();

        if (!$enrollment) return 0;

        $requiredCourseIds = DB::table('learning_path_courses')
            ->where('learning_path_id', $path->id)
            ->where('is_required', true)
            ->pluck('course_id')
            ->toArray();

        if (empty($requiredCourseIds)) {
            return 0;
        }

        $completedCount = CourseEnrollment::where('student_id', $user->id)
            ->whereIn('course_id', $requiredCourseIds)
            ->where('progress_percentage', 100)
            ->count();

        $progress = round(($completedCount / count($requiredCourseIds)) * 100, 2);

        $enrollment->update([
            'progress_percentage' => $progress,
            'status'              => $progress >= 100 ? 'completed' : 'in_progress',
            'completed_at'        => $progress >= 100 ? now() : null,
        ]);

        // ── Milestone XP rewards (25 / 50 / 75 / 100 %) ──────────────────────
        $milestones = [
            25  => ['xp' =>  30, 'label' => '25% من المسار'],
            50  => ['xp' =>  60, 'label' => 'منتصف المسار'],
            75  => ['xp' =>  90, 'label' => '75% من المسار'],
            100 => null, // handled below via onPathCompleted
        ];

        $lastRewarded = $enrollment->last_milestone_rewarded;
        $gamification = app(GamificationService::class);

        foreach ($milestones as $threshold => $reward) {
            if ($threshold === 100 || $threshold <= $lastRewarded) continue;
            if ($progress >= $threshold) {
                $gamification->awardXp($user, $reward['xp'], "path_milestone_{$threshold}");
                $enrollment->update(['last_milestone_rewarded' => $threshold]);
                $lastRewarded = $threshold;

                NotificationService::send(
                    $user,
                    "🏁 وصلت إلى {$threshold}%!",
                    "أحرزت {$reward['label']} في \"{$path->title}\". استمر!",
                    'achievement',
                    ['path_id' => $path->id, 'action' => 'open_path']
                );
            }
        }

        // Trigger gamification on path completion
        if ($progress >= 100 && $enrollment->wasChanged('status')) {
            $gamification->onPathCompleted($user, $path);
        }

        return $progress;
    }

    /**
     * Create a learning path (by teacher or admin).
     */
    public function createPath(User $creator, array $data): LearningPath
    {
        return DB::transaction(function () use ($creator, $data) {
            $path = LearningPath::create([
                'created_by'       => $creator->id,
                'category_id'      => $data['category_id'] ?? null,
                'title'            => $data['title'],
                'slug'             => Str::slug($data['title']) . '-' . substr(uniqid(), -5),
                'description'      => $data['description'] ?? null,
                'thumbnail'        => $data['thumbnail'] ?? null,
                'difficulty_level' => $data['difficulty_level'] ?? 'beginner',
                'estimated_hours'  => $data['estimated_hours'] ?? 0,
                'skill_tags'       => $data['skill_tags'] ?? [],
                'status'           => 'draft',
            ]);

            if (!empty($data['course_ids'])) {
                $this->syncCourses($path, $data['course_ids']);
            }

            return $path;
        });
    }

    /**
     * Sync ordered course list into a learning path.
     *
     * @param  array $courseIds  Ordered array of course IDs.
     */
    public function syncCourses(LearningPath $path, array $courseIds): void
    {
        $pivotData = [];
        foreach ($courseIds as $order => $courseId) {
            $pivotData[$courseId] = ['order' => $order, 'is_required' => true];
        }

        $path->courses()->sync($pivotData);
        $path->update(['courses_count' => count($courseIds)]);
    }
}
