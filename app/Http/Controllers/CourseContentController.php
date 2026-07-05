<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\CourseSection;
use App\Models\CourseLesson;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class CourseContentController extends Controller
{
    // --- Sections Management ---

    public function storeSection(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);

        // Authorization check
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        // Get max order
        $maxOrder = CourseSection::where('course_id', $courseId)->max('order') ?? 0;

        $section = CourseSection::create([
            'course_id' => $courseId,
            'title' => $request->title,
            'description' => $request->description,
            'order' => $maxOrder + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Section created successfully',
            'data' => $section
        ], 201);
    }

    public function updateSection(Request $request, $courseId, $sectionId)
    {
        $course = Course::findOrFail($courseId);
        
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $section = CourseSection::where('course_id', $courseId)->findOrFail($sectionId);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $section->update($request->only(['title', 'description']));

        return response()->json([
            'success' => true,
            'message' => 'Section updated successfully',
            'data' => $section
        ]);
    }

    public function deleteSection(Request $request, $courseId, $sectionId)
    {
        $course = Course::findOrFail($courseId);
        
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $section = CourseSection::where('course_id', $courseId)->findOrFail($sectionId);
        
        // Check if section has lessons
        if ($section->lessons()->count() > 0) {
            return response()->json([
                'success' => false, 
                'message' => 'Cannot delete section with lessons. Delete lessons first.'
            ], 400);
        }

        $section->delete();

        return response()->json([
            'success' => true,
            'message' => 'Section deleted successfully'
        ]);
    }

    public function reorderSections(Request $request, $courseId)
    {
        $course = Course::findOrFail($courseId);
        
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:course_sections,id',
            'orders.*.order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $courseId) {
            foreach ($request->orders as $item) {
                CourseSection::where('id', $item['id'])
                    ->where('course_id', $courseId)
                    ->update(['order' => $item['order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Sections reordered successfully'
        ]);
    }

    // --- Lessons Management ---

    public function storeLesson(Request $request, $courseId, $sectionId)
    {
        $course = Course::findOrFail($courseId);
        
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $section = CourseSection::where('course_id', $courseId)->findOrFail($sectionId);

        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:video,text,quiz,file',
            'is_preview' => 'boolean',
            'duration' => 'nullable|integer', // in seconds
            'content_file' => 'nullable|file|max:102400', // 100MB max for now
            'content_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['title', 'description', 'type', 'is_preview', 'duration', 'content_url']);
        $data['course_id'] = $courseId;
        $data['section_id'] = $sectionId;

        // Handle File Upload
        if ($request->hasFile('content_file')) {
            $path = $request->file('content_file')->store('course-content', 'public');
            $data['content_file'] = $path;
            // If type is video and no duration provided, we might want to extract it later
        }

        // Get max order in section
        $maxOrder = CourseLesson::where('section_id', $sectionId)->max('order') ?? 0;
        $data['order'] = $maxOrder + 1;

        $lesson = CourseLesson::create($data);

        // Update course lectures count and duration
        $this->updateCourseStats($course);

        return response()->json([
            'success' => true,
            'message' => 'Lesson created successfully',
            'data' => $lesson
        ], 201);
    }

    public function updateLesson(Request $request, $courseId, $sectionId, $lessonId)
    {
        $course = Course::findOrFail($courseId);
        
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $lesson = CourseLesson::where('course_id', $courseId)
            ->where('section_id', $sectionId)
            ->findOrFail($lessonId);

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'type' => 'sometimes|in:video,text,quiz,file',
            'is_preview' => 'boolean',
            'duration' => 'nullable|integer',
            'content_file' => 'nullable|file|max:102400',
            'content_url' => 'nullable|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $data = $request->only(['title', 'description', 'type', 'is_preview', 'duration', 'content_url']);

        if ($request->hasFile('content_file')) {
            // Delete old file if exists
            if ($lesson->content_file) {
                Storage::disk('public')->delete($lesson->content_file);
            }
            $path = $request->file('content_file')->store('course-content', 'public');
            $data['content_file'] = $path;
        }

        $lesson->update($data);
        
        if ($request->has('duration')) {
            $this->updateCourseStats($course);
        }

        return response()->json([
            'success' => true,
            'message' => 'Lesson updated successfully',
            'data' => $lesson
        ]);
    }

    public function deleteLesson(Request $request, $courseId, $sectionId, $lessonId)
    {
        $course = Course::findOrFail($courseId);
        
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $lesson = CourseLesson::where('course_id', $courseId)
            ->where('section_id', $sectionId)
            ->findOrFail($lessonId);

        if ($lesson->content_file) {
            Storage::disk('public')->delete($lesson->content_file);
        }

        $lesson->delete();

        $this->updateCourseStats($course);

        return response()->json([
            'success' => true,
            'message' => 'Lesson deleted successfully'
        ]);
    }

    public function reorderLessons(Request $request, $courseId, $sectionId)
    {
        $course = Course::findOrFail($courseId);
        
        if ($course->teacher_id != $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'orders' => 'required|array',
            'orders.*.id' => 'required|exists:course_lessons,id',
            'orders.*.order' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        DB::transaction(function () use ($request, $sectionId) {
            foreach ($request->orders as $item) {
                CourseLesson::where('id', $item['id'])
                    ->where('section_id', $sectionId)
                    ->update(['order' => $item['order']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Lessons reordered successfully'
        ]);
    }

    private function updateCourseStats(Course $course)
    {
        $lecturesCount = $course->lessons()->count();
        $totalDuration = $course->lessons()->sum('duration'); // in seconds

        // Convert seconds to formatted string if needed, but storing seconds is better for calculation
        // Assuming 'duration' in Course model is string (e.g. "10h 30m") or integer (seconds)
        // Let's assume we store it as a string for display for now, or update the model to be integer.
        // Based on previous read, Course model has 'duration' field. Let's store formatted string.
        
        $hours = floor($totalDuration / 3600);
        $minutes = floor(($totalDuration % 3600) / 60);
        $durationString = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";

        $course->update([
            'lectures_count' => $lecturesCount,
            'duration' => $durationString
        ]);
    }
}
