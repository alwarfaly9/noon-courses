<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourseRequest;
use App\Http\Requests\Admin\UpdateCourseRequest;
use App\Mail\CourseApprovedMail;
use App\Mail\CourseRejectedMail;
use App\Models\Category;
use App\Models\Course;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CourseController extends Controller
{
    /**
     * List courses (admin sees all, teacher sees own).
     */
    public function index(Request $request)
    {
        $query = Course::with(['teacher', 'category']);

        if (auth()->user()->hasRole('teacher')) {
            $query->where('teacher_id', auth()->id());
        }

        if ($request->status && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $courses = $query->latest()->paginate(20);
        return view('admin.courses', compact('courses'));
    }

    /**
     * Show create course form.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        if (auth()->user()->hasRole('teacher')) {
            $teachers = collect([auth()->user()]);
        } else {
            $teachers = User::whereHas('roles', function ($q) {
                $q->where('name', 'teacher');
            })->orderBy('name')->get();
        }

        $course = new Course();
        return view('admin.course-form', [
            'mode' => 'create',
            'course' => $course,
            'categories' => $categories,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Store a new course.
     */
    public function store(StoreCourseRequest $request)
    {
        $data = $request->validated();

        if (auth()->user()->hasRole('teacher')) {
            $data['teacher_id'] = auth()->id();
        }

        Course::create([
            'title' => $data['title'],
            'slug' => Str::slug($data['title']) . '-' . substr(uniqid(), -5),
            'teacher_id' => $data['teacher_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'short_description' => $data['short_description'] ?? null,
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'requirements' => $this->parseTextToArray($data['requirements_text'] ?? null),
            'what_you_will_learn' => $this->parseTextToArray($data['learn_text'] ?? null),
            'level' => $data['level'],
            'language' => $data['language'],
            'status' => auth()->user()->hasRole('admin') ? 'published' : 'pending',
            'published_at' => auth()->user()->hasRole('admin') ? now() : null,
        ]);

        return redirect()->route('admin.courses')->with('success', 'تم إنشاء الدورة بنجاح');
    }

    /**
     * Show edit course form.
     */
    public function edit($id)
    {
        $course = Course::findOrFail($id);

        if (auth()->user()->hasRole('teacher') && $course->teacher_id != auth()->id()) {
            abort(403);
        }

        $categories = Category::orderBy('name')->get();

        if (auth()->user()->hasRole('teacher')) {
            $teachers = collect([auth()->user()]);
        } else {
            $teachers = User::whereHas('roles', function ($q) {
                $q->where('name', 'teacher');
            })->orderBy('name')->get();
        }

        return view('admin.course-form', [
            'mode' => 'edit',
            'course' => $course,
            'categories' => $categories,
            'teachers' => $teachers,
        ]);
    }

    /**
     * Update an existing course.
     */
    public function update(UpdateCourseRequest $request, $id)
    {
        $course = Course::findOrFail($id);

        if (auth()->user()->hasRole('teacher')) {
            if ($course->teacher_id != auth()->id()) {
                abort(403);
            }
            $request->merge(['teacher_id' => auth()->id()]);
        }

        $data = $request->validated();

        $newStatus = auth()->user()->hasRole('admin') ? ($data['status'] ?? $course->status) : $course->status;

        $course->update([
            'title' => $data['title'],
            'teacher_id' => $data['teacher_id'],
            'category_id' => $data['category_id'],
            'description' => $data['description'],
            'short_description' => $data['short_description'] ?? null,
            'price' => $data['price'],
            'discount_price' => $data['discount_price'] ?? null,
            'requirements' => $this->parseTextToArray($data['requirements_text'] ?? null),
            'what_you_will_learn' => $this->parseTextToArray($data['learn_text'] ?? null),
            'level' => $data['level'],
            'language' => $data['language'],
            'status' => $newStatus,
            'published_at' => $newStatus === 'published' ? ($course->published_at ?? now()) : $course->published_at,
        ]);

        return redirect()->route('admin.courses')->with('success', 'تم تحديث الدورة بنجاح');
    }

    /**
     * Approve a pending course and notify the teacher.
     */
    public function approve($id)
    {
        $course = Course::with('teacher')->findOrFail($id);
        $course->update(['status' => 'published', 'published_at' => now()]);

        if ($course->teacher) {
            $teacher = $course->teacher;

            if ($teacher->email) {
                Mail::to($teacher->email)->queue(new CourseApprovedMail($course));
            }

            NotificationService::send(
                $teacher,
                '✅ تمت الموافقة على دورتك',
                "تمت الموافقة على دورتك \"{$course->title}\" وهي الآن منشورة.",
                'course',
                ['course_id' => $course->id]
            );
        }

        return back()->with('success', 'تمت الموافقة على الدورة وإشعار المعلم');
    }

    /**
     * Reject a course and notify the teacher with a reason.
     */
    public function reject(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        $course = Course::with('teacher')->findOrFail($id);
        $reason = $request->input('reason');

        $course->update([
            'status'           => 'rejected',
            'rejection_reason' => $reason,
        ]);

        if ($course->teacher) {
            $teacher = $course->teacher;

            if ($teacher->email) {
                Mail::to($teacher->email)->queue(new CourseRejectedMail($course, $reason));
            }

            NotificationService::send(
                $teacher,
                '❌ تم رفض دورتك',
                "عذراً، لم تتم الموافقة على دورتك \"{$course->title}\". السبب: {$reason}",
                'course',
                ['course_id' => $course->id]
            );
        }

        return back()->with('success', 'تم رفض الدورة وإشعار المعلم');
    }

    /**
     * Parse multiline text into array.
     */
    private function parseTextToArray(?string $text): ?array
    {
        if (!$text) {
            return null;
        }
        return array_values(array_filter(array_map('trim', preg_split("/(\r\n|\n|\r)/", $text))));
    }
}
