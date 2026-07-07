<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Story;
use App\Services\StoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StoryController extends Controller
{
    public function __construct(private StoryService $storyService) {}

    public function index()
    {
        $stories = $this->storyService->getTeacherStories(auth()->user());
        return view('teacher.stories', compact('stories'));
    }

    public function create()
    {
        $courses = auth()->user()->teachingCourses()->select('id', 'title')->get();
        return view('teacher.story-form', compact('courses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id'  => 'nullable|exists:courses,id',
            'title'      => 'required|string|max:255',
            'body'       => 'nullable|string',
            'media'      => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi|max:102400',
            'expires_at' => 'nullable|date|after:now',
        ]);

        if ($data['course_id'] ?? null) {
            $owns = auth()->user()->teachingCourses()->where('course_id', $data['course_id'])->exists();
            abort_unless($owns, 403, 'لا تملك هذه الدورة');
        }

        $story = $this->storyService->createStory(auth()->user(), $data);

        return redirect()->route('teacher.stories.index')
            ->with('success', 'تم نشر القصة بنجاح');
    }

    public function edit(Story $story)
    {
        abort_if($story->user_id !== auth()->id(), 403);
        $courses = auth()->user()->teachingCourses()->select('id', 'title')->get();
        return view('teacher.story-form', compact('story', 'courses'));
    }

    public function update(Request $request, Story $story)
    {
        abort_if($story->user_id !== auth()->id(), 403);

        $data = $request->validate([
            'course_id'  => 'nullable|exists:courses,id',
            'title'      => 'string|max:255',
            'body'       => 'nullable|string',
            'media'      => 'nullable|file|mimes:jpg,jpeg,png,gif,webp,mp4,mov,avi|max:102400',
            'expires_at' => 'nullable|date|after:now',
            'is_active'  => 'boolean',
        ]);

        if (isset($data['course_id']) && $data['course_id']) {
            $owns = auth()->user()->teachingCourses()->where('course_id', $data['course_id'])->exists();
            abort_unless($owns, 403, 'لا تملك هذه الدورة');
        }

        $this->storyService->updateStory($story, $data);

        return redirect()->route('teacher.stories.index')
            ->with('success', 'تم تحديث القصة بنجاح');
    }

    public function destroy(Story $story)
    {
        abort_if($story->user_id !== auth()->id(), 403);

        if ($story->media_path) {
            Storage::disk('public')->delete($story->media_path);
        }

        $story->delete();
        return back()->with('success', 'تم حذف القصة');
    }
}
