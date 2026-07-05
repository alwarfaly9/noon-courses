<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Services\GamificationService;
use App\Services\LearningPathService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StudentProgressController extends Controller
{
    public function markLessonComplete(Request $request, $courseId, $lessonId)
    {
        $user = $request->user();

        // Verify enrollment
        $enrollment = CourseEnrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->firstOrFail();

        // Verify lesson belongs to course
        $lesson = CourseLesson::where('course_id', $courseId)
            ->findOrFail($lessonId);

        // We need a table to track individual lesson completion. 
        // Since I don't see a 'lesson_completions' table in the file list, 
        // I will assume we need to create a pivot table or use a JSON field in enrollment.
        // However, for a robust system, a separate table is best.
        // For now, let's assume we'll create a migration for `lesson_completions` table.
        // But to make it work with existing structure, I'll check if I can use a simple approach.
        
        // Let's check if we can attach to a relationship.
        // If `lesson_completions` doesn't exist, I should create it.
        // I'll create the migration in the next step.
        
        // Assuming the relationship exists on User model: $user->completedLessons()
        // Or we can just insert into the DB directly if the model isn't set up yet.
        
        $exists = DB::table('lesson_completions')
            ->where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->exists();

        if (!$exists) {
            DB::table('lesson_completions')->insert([
                'user_id' => $user->id,
                'lesson_id' => $lessonId,
                'course_id' => $courseId,
                'completed_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Recalculate progress
        $totalLessons = CourseLesson::where('course_id', $courseId)->count();
        $completedLessons = DB::table('lesson_completions')
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->count();

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $wasCompleted = $enrollment->progress_percentage >= 100;
        $enrollment->update([
            'progress_percentage' => $progress,
            'completed_at'        => $progress == 100 ? ($enrollment->completed_at ?? now()) : null,
        ]);

        // ── Gamification hooks ────────────────────────────────────────────────
        $gamification = app(GamificationService::class);
        if (!$exists) {
            // New lesson completion
            $gamification->onLessonCompleted($user);
        }

        // Course just reached 100%
        if ($progress >= 100 && !$wasCompleted) {
            $course = Course::find($courseId);
            if ($course) {
                $gamification->onCourseCompleted($user, $course);
                // Recalculate any learning paths that include this course
                $this->recalculatePathsForCourse($user, $courseId);
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as complete',
            'data'    => [
                'progress'     => $progress,
                'is_completed' => $progress >= 100,
            ],
        ]);
    }
    
    public function markLessonIncomplete(Request $request, $courseId, $lessonId)
    {
        $user = $request->user();
        
        // Verify enrollment
        $enrollment = CourseEnrollment::where('student_id', $user->id)
            ->where('course_id', $courseId)
            ->firstOrFail();

        DB::table('lesson_completions')
            ->where('user_id', $user->id)
            ->where('lesson_id', $lessonId)
            ->delete();

        // Recalculate progress
        $totalLessons = CourseLesson::where('course_id', $courseId)->count();
        $completedLessons = DB::table('lesson_completions')
            ->where('user_id', $user->id)
            ->where('course_id', $courseId)
            ->count();

        $progress = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100) : 0;

        $enrollment->update([
            'progress_percentage' => $progress,
            'completed_at'        => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Lesson marked as incomplete',
            'data'    => ['progress' => $progress],
        ]);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function recalculatePathsForCourse($user, int $courseId): void
    {
        $pathIds = DB::table('learning_path_courses')
            ->where('course_id', $courseId)
            ->pluck('learning_path_id');

        if ($pathIds->isEmpty()) return;

        $service = app(LearningPathService::class);

        foreach ($pathIds as $pathId) {
            $path = \App\Models\LearningPath::find($pathId);
            if ($path) {
                $service->recalculateProgress($user, $path);
            }
        }
    }
}
