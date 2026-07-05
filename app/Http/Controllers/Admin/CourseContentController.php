<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use App\Models\Course;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CourseContentController extends Controller
{
    /**
     * Show course content manager (sections & lessons).
     */
    public function show($id)
    {
        $course = Course::with(['sections.lessons' => function ($q) {
            $q->orderBy('order');
        }])->findOrFail($id);

        return view('admin.course-content', compact('course'));
    }

    /**
     * Add a section to a course.
     */
    public function storeSection(Request $request, $id)
    {
        $course = Course::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $nextOrder = (int) ($course->sections()->max('order') ?? 0) + 1;
        $course->sections()->create([
            'title' => $data['title'],
            'order' => $nextOrder,
        ]);

        return back()->with('success', 'تم إضافة قسم جديد');
    }

    /**
     * Add a lesson to a section.
     */
    public function storeLesson(Request $request, $sectionId)
    {
        $section = CourseSection::findOrFail($sectionId);
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'duration_text' => 'nullable|string',
            'video_file' => 'nullable|file|mimes:mp4,mov,mkv,avi,webm|max:512000',
            'subtitle_file' => 'nullable|file|mimes:vtt,srt,txt|max:10240',
        ]);

        $durationSeconds = $this->parseDuration($data['duration_text'] ?? null);

        $nextOrder = (int) ($section->lessons()->max('order') ?? 0) + 1;
        $lesson = $section->lessons()->create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'duration' => $durationSeconds,
            'order' => $nextOrder,
            'course_id' => $section->course_id,
        ]);

        $this->handleLessonFiles($request, $lesson, $section->course_id);

        return back()->with('success', 'تم إضافة محاضرة');
    }

    /**
     * Delete a section.
     */
    public function deleteSection($sectionId)
    {
        CourseSection::findOrFail($sectionId)->delete();
        return back()->with('success', 'تم حذف القسم');
    }

    /**
     * Delete a lesson.
     */
    public function deleteLesson($lessonId)
    {
        CourseLesson::findOrFail($lessonId)->delete();
        return back()->with('success', 'تم حذف المحاضرة');
    }

    /**
     * Upload files for a lesson.
     */
    public function uploadLessonFile(Request $request, $lessonId)
    {
        $lesson = CourseLesson::with('course')->findOrFail($lessonId);
        $request->validate([
            'file' => 'nullable|file|mimes:mp4,mov,mkv,avi,webm,mp3,pdf,doc,docx,ppt,pptx,zip,rar|max:512000',
            'subtitle' => 'nullable|file|mimes:vtt,srt,txt|max:10240',
        ]);

        $disk = Storage::disk('private');
        $folder = 'courses/' . $lesson->course_id . '/lessons/' . $lesson->id;

        if ($request->hasFile('file')) {
            $path = $disk->putFile($folder, $request->file('file'));
            $mime = $request->file('file')->getClientMimeType();
            $isVideo = str_starts_with($mime, 'video/') || in_array($request->file('file')->getClientOriginalExtension(), ['mp4', 'mov', 'mkv', 'avi', 'webm']);
            $lesson->type = $isVideo ? 'video' : 'document';
            if ($isVideo) {
                $lesson->content_url = $path;
            } else {
                $lesson->content_file = $path;
            }
        }

        if ($request->hasFile('subtitle')) {
            $subPath = $disk->putFile($folder, $request->file('subtitle'));
            $lesson->subtitle_file = $subPath;
        }

        if ($lesson->isDirty()) {
            $lesson->save();
        }

        return back()->with('success', 'تم رفع الملف بنجاح');
    }

    /**
     * Parse duration text (e.g. "1:30:00", "45:00", "90") into seconds.
     */
    private function parseDuration(?string $text): ?int
    {
        if (empty($text)) {
            return null;
        }

        $parts = array_map('trim', explode(':', $text));
        if (count($parts) === 3) {
            return (int) $parts[0] * 3600 + (int) $parts[1] * 60 + (int) $parts[2];
        } elseif (count($parts) === 2) {
            return (int) $parts[0] * 60 + (int) $parts[1];
        }
        return (int) $parts[0];
    }

    /**
     * Handle file uploads for a lesson (private storage).
     */
    private function handleLessonFiles(Request $request, CourseLesson $lesson, int $courseId): void
    {
        $disk = Storage::disk('private');
        $folder = 'courses/' . $courseId . '/lessons/' . $lesson->id;

        if ($request->hasFile('video_file')) {
            $videoPath = $disk->putFile($folder, $request->file('video_file'));
            $lesson->type = 'video';
            $lesson->content_url = $videoPath;
        }

        if ($request->hasFile('subtitle_file')) {
            $subPath = $disk->putFile($folder, $request->file('subtitle_file'));
            $lesson->subtitle_file = $subPath;
        }

        if ($lesson->isDirty()) {
            $lesson->save();
        }
    }
}
