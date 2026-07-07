<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\Course;
use Illuminate\Http\Request;

class ChallengeController extends Controller
{
    public function index()
    {
        $challenges = Campaign::where('created_by', auth()->id())
            ->latest()
            ->paginate(20);

        return view('teacher.challenges', compact('challenges'));
    }

    public function create()
    {
        $courses = auth()->user()->teachingCourses()->select('id', 'title')->get();
        return view('teacher.challenge-form', compact('courses'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id'       => 'nullable|exists:courses,id',
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'type'            => 'required|in:quiz_streak,lesson_completion,participation,enrollment',
            'goal_type'       => 'required|string',
            'goal_value'      => 'required|integer|min:1',
            'starts_at'       => 'required|date',
            'ends_at'         => 'required|date|after:starts_at',
            'reward_type'     => 'required|in:xp,badge,certificate',
            'reward_value'    => 'required|integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
        ]);

        $data['created_by'] = auth()->id();
        $data['is_active'] = true;

        Campaign::create($data);

        return redirect()->route('teacher.challenges.index')
            ->with('success', 'تم إنشاء التحدي بنجاح');
    }

    public function edit(Campaign $challenge)
    {
        abort_if($challenge->created_by !== auth()->id(), 403);
        $courses = auth()->user()->teachingCourses()->select('id', 'title')->get();
        return view('teacher.challenge-form', compact('challenge', 'courses'));
    }

    public function update(Request $request, Campaign $challenge)
    {
        abort_if($challenge->created_by !== auth()->id(), 403);

        $data = $request->validate([
            'course_id'        => 'nullable|exists:courses,id',
            'title'            => 'string|max:255',
            'description'      => 'nullable|string',
            'type'             => 'in:quiz_streak,lesson_completion,participation,enrollment',
            'goal_type'        => 'string',
            'goal_value'       => 'integer|min:1',
            'starts_at'        => 'date',
            'ends_at'          => 'date|after:starts_at',
            'reward_type'      => 'in:xp,badge,certificate',
            'reward_value'     => 'integer|min:1',
            'max_participants' => 'nullable|integer|min:1',
            'is_active'        => 'boolean',
        ]);

        $challenge->update($data);

        return redirect()->route('teacher.challenges.index')
            ->with('success', 'تم تحديث التحدي بنجاح');
    }

    public function destroy(Campaign $challenge)
    {
        abort_if($challenge->created_by !== auth()->id(), 403);
        $challenge->delete();
        return back()->with('success', 'تم حذف التحدي');
    }

    public function participants(Campaign $challenge)
    {
        abort_if($challenge->created_by !== auth()->id(), 403);

        $participants = $challenge->participations()
            ->with('user:id,name,email')
            ->latest()
            ->paginate(20);

        return view('teacher.challenge-participants', compact('challenge', 'participants'));
    }
}
