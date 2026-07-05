<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Course;
use App\Models\CourseEnrollment;
use App\Models\CourseLesson;
use App\Models\CourseSection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * Teacher dashboard: stats + recent enrollments.
     */
    public function dashboard()
    {
        $teacherId = Auth::id();

        $courseIds = Course::where('teacher_id', $teacherId)->pluck('id');

        $totalCourses     = $courseIds->count();
        $publishedCourses = Course::where('teacher_id', $teacherId)->where('status', 'published')->count();
        $pendingCourses   = Course::where('teacher_id', $teacherId)->where('status', 'pending')->count();
        $totalStudents    = CourseEnrollment::whereIn('course_id', $courseIds)->count();
        $totalEarnings    = CourseEnrollment::whereIn('course_id', $courseIds)
                                ->where('status', 'active')
                                ->join('courses', 'course_enrollments.course_id', '=', 'courses.id')
                                ->sum('courses.price');

        $recentEnrollments = CourseEnrollment::with(['student', 'course'])
            ->whereIn('course_id', $courseIds)
            ->latest()
            ->take(10)
            ->get();

        return view('teacher.dashboard', compact(
            'totalCourses', 'publishedCourses', 'pendingCourses',
            'totalStudents', 'totalEarnings', 'recentEnrollments'
        ));
    }

    /**
     * List teacher's courses.
     */
    public function index()
    {
        $courses = Course::where('teacher_id', Auth::id())
                         ->withCount('students')
                         ->with('category')
                         ->latest()
                         ->paginate(20);
        return view('teacher.courses', compact('courses'));
    }

    /**
     * Show create course form.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('teacher.course-form', compact('categories'));
    }

    /**
     * Show edit course form.
     */
    public function edit($id)
    {
        $course = Course::where('teacher_id', Auth::id())->findOrFail($id);
        $categories = Category::orderBy('name')->get();
        return view('teacher.course-form', compact('course', 'categories'));
    }

    /**
     * Store a new course (submitted for review).
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'category_id'       => 'required|exists:categories,id',
            'description'       => 'required|string',
            'short_description' => 'nullable|string',
            'price'             => 'required|numeric|min:0',
            'discount_price'    => 'nullable|numeric|min:0',
            'requirements_text' => 'nullable|string',
            'learn_text'        => 'nullable|string',
            'level'             => 'required|in:beginner,intermediate,advanced',
            'language'          => 'required|in:ar,en',
            'image'             => 'nullable|image|max:2048',
        ]);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('courses', 'public')
            : null;

        Course::create([
            'title'              => $data['title'],
            'slug'               => Str::slug($data['title']) . '-' . substr(uniqid(), -5),
            'teacher_id'         => Auth::id(),
            'category_id'        => $data['category_id'],
            'description'        => $data['description'],
            'short_description'  => $data['short_description'] ?? null,
            'price'              => $data['price'],
            'discount_price'     => $data['discount_price'] ?? null,
            'requirements'       => $this->parseTextToArray($data['requirements_text'] ?? null),
            'what_you_will_learn'=> $this->parseTextToArray($data['learn_text'] ?? null),
            'level'              => $data['level'],
            'language'           => $data['language'],
            'image'              => $imagePath,
            'status'             => 'pending',
        ]);

        return redirect()->route('teacher.courses')->with('success', 'تم إرسال الدورة للمراجعة بنجاح');
    }

    /**
     * Update an existing course.
     */
    public function update(Request $request, $id)
    {
        $course = Course::where('teacher_id', Auth::id())->findOrFail($id);

        // Prevent editing a published course without re-review
        if ($course->status === 'published') {
            $course->status = 'pending';
        }

        $data = $request->validate([
            'title'             => 'required|string|max:255',
            'category_id'       => 'required|exists:categories,id',
            'description'       => 'required|string',
            'short_description' => 'nullable|string',
            'price'             => 'required|numeric|min:0',
            'discount_price'    => 'nullable|numeric|min:0',
            'requirements_text' => 'nullable|string',
            'learn_text'        => 'nullable|string',
            'level'             => 'required|in:beginner,intermediate,advanced',
            'language'          => 'required|in:ar,en',
            'image'             => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($course->image) {
                Storage::disk('public')->delete($course->image);
            }
            $data['image'] = $request->file('image')->store('courses', 'public');
        }

        $course->update([
            'title'              => $data['title'],
            'category_id'        => $data['category_id'],
            'description'        => $data['description'],
            'short_description'  => $data['short_description'] ?? null,
            'price'              => $data['price'],
            'discount_price'     => $data['discount_price'] ?? null,
            'requirements'       => $this->parseTextToArray($data['requirements_text'] ?? null),
            'what_you_will_learn'=> $this->parseTextToArray($data['learn_text'] ?? null),
            'level'              => $data['level'],
            'language'           => $data['language'],
            'image'              => $data['image'] ?? $course->image,
            'status'             => $course->status,
        ]);

        return redirect()->route('teacher.courses')->with('success', 'تم تحديث الدورة وإعادة إرسالها للمراجعة');
    }

    /**
     * Soft-delete a course (only if not published).
     */
    public function destroy($id)
    {
        $course = Course::where('teacher_id', Auth::id())->findOrFail($id);

        if ($course->status === 'published' && $course->students()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف دورة منشورة وبها طلاب مسجلون');
        }

        $course->delete();
        return redirect()->route('teacher.courses')->with('success', 'تم حذف الدورة');
    }

    /**
     * Course content management (sections & lessons).
     */
    public function content($id)
    {
        $course = Course::where('teacher_id', Auth::id())
                        ->with(['sections' => function ($q) {
                            $q->orderBy('order')->with(['lessons' => function ($q2) {
                                $q2->orderBy('order');
                            }]);
                        }])
                        ->findOrFail($id);

        return view('teacher.course-content', compact('course'));
    }

    // ─── Section Management ───────────────────────────────────────────────────

    public function storeSection(Request $request, $courseId)
    {
        $course = Course::where('teacher_id', Auth::id())->findOrFail($courseId);
        $data = $request->validate(['title' => 'required|string|max:255']);

        $nextOrder = (int)($course->sections()->max('order') ?? 0) + 1;
        $course->sections()->create(['title' => $data['title'], 'order' => $nextOrder]);

        return back()->with('success', 'تم إضافة القسم');
    }

    public function deleteSection($sectionId)
    {
        $section = CourseSection::findOrFail($sectionId);
        // Ensure teacher owns the course
        Course::where('teacher_id', Auth::id())->findOrFail($section->course_id);
        $section->delete();
        return back()->with('success', 'تم حذف القسم');
    }

    // ─── Lesson Management ───────────────────────────────────────────────────

    public function storeLesson(Request $request, $sectionId)
    {
        $section = CourseSection::findOrFail($sectionId);
        Course::where('teacher_id', Auth::id())->findOrFail($section->course_id);

        $data = $request->validate([
            'title'         => 'required|string|max:255',
            'description'   => 'nullable|string',
            'duration_text' => 'nullable|string',
            'video_file'    => 'nullable|file|mimes:mp4,mov,mkv,avi,webm|max:512000',
            'subtitle_file' => 'nullable|file|mimes:vtt,srt,txt|max:10240',
        ]);

        $durationSeconds = $this->parseDuration($data['duration_text'] ?? null);
        $nextOrder = (int)($section->lessons()->max('order') ?? 0) + 1;

        $lesson = $section->lessons()->create([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'duration'    => $durationSeconds,
            'order'       => $nextOrder,
            'course_id'   => $section->course_id,
        ]);

        $this->handleLessonFiles($request, $lesson, $section->course_id);
        return back()->with('success', 'تم إضافة المحاضرة');
    }

    public function deleteLesson($lessonId)
    {
        $lesson = CourseLesson::findOrFail($lessonId);
        Course::where('teacher_id', Auth::id())->findOrFail($lesson->course_id);
        $lesson->delete();
        return back()->with('success', 'تم حذف المحاضرة');
    }

    public function uploadLesson(Request $request, $lessonId)
    {
        $lesson = CourseLesson::with('section.course')->findOrFail($lessonId);
        Course::where('teacher_id', Auth::id())->findOrFail($lesson->course_id);

        $request->validate([
            'file'     => 'nullable|file|mimes:mp4,mov,mkv,avi,webm,mp3,pdf,doc,docx,ppt,pptx,zip,rar|max:512000',
            'subtitle' => 'nullable|file|mimes:vtt,srt,txt|max:10240',
        ]);

        $this->handleLessonUpload($request, $lesson);
        return back()->with('success', 'تم رفع الملف');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function parseTextToArray(?string $text): ?array
    {
        if (!$text) {
            return null;
        }
        return array_values(array_filter(array_map('trim', preg_split("/(\r\n|\n|\r)/", $text))));
    }

    private function parseDuration(?string $text): ?int
    {
        if (empty($text)) return null;
        $parts = array_map('trim', explode(':', $text));
        if (count($parts) === 3) return (int)$parts[0] * 3600 + (int)$parts[1] * 60 + (int)$parts[2];
        if (count($parts) === 2) return (int)$parts[0] * 60 + (int)$parts[1];
        return (int)$parts[0];
    }

    private function handleLessonFiles(Request $request, CourseLesson $lesson, int $courseId): void
    {
        $disk   = Storage::disk('private');
        $folder = 'courses/' . $courseId . '/lessons/' . $lesson->id;

        if ($request->hasFile('video_file')) {
            $path = $disk->putFile($folder, $request->file('video_file'));
            $lesson->type = 'video';
            $lesson->content_url = $path;
        }
        if ($request->hasFile('subtitle_file')) {
            $lesson->subtitle_file = $disk->putFile($folder, $request->file('subtitle_file'));
        }
        if ($lesson->isDirty()) $lesson->save();
    }

    private function handleLessonUpload(Request $request, CourseLesson $lesson): void
    {
        $disk   = Storage::disk('private');
        $folder = 'courses/' . $lesson->course_id . '/lessons/' . $lesson->id;

        if ($request->hasFile('file')) {
            $mime    = $request->file('file')->getClientMimeType();
            $isVideo = str_starts_with($mime, 'video/');
            $path    = $disk->putFile($folder, $request->file('file'));
            $lesson->type = $isVideo ? 'video' : 'document';
            if ($isVideo) $lesson->content_url  = $path;
            else          $lesson->content_file = $path;
        }
        if ($request->hasFile('subtitle')) {
            $lesson->subtitle_file = $disk->putFile($folder, $request->file('subtitle'));
        }
        if ($lesson->isDirty()) $lesson->save();
    }
}
