<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\CourseSection;
use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    public function index(Course $course)
    {
        $quizzes = Quiz::where('course_id', $course->id)
            ->withCount('attempts', 'questions')
            ->get();

        return view('teacher.quizzes', compact('course', 'quizzes'));
    }

    public function create(Course $course)
    {
        $sections = $course->sections()->select('id', 'title')->get();
        return view('teacher.quiz-form', compact('course', 'sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'course_id'         => 'required|exists:courses,id',
            'course_section_id' => 'required|exists:course_sections,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'duration_minutes'  => 'nullable|integer|min:0',
            'pass_mark'         => 'required|integer|min:1|max:100',
        ]);

        $course = Course::findOrFail($data['course_id']);
        abort_if($course->teacher_id !== auth()->id(), 403, 'لا تملك هذه الدورة');

        $quiz = DB::transaction(function () use ($data) {
            $quiz = Quiz::create($data);

            if ($questions = request('questions')) {
                foreach ($questions as $qData) {
                    $question = $quiz->questions()->create([
                        'content' => $qData['content'],
                        'type'    => $qData['type'] ?? 'multiple_choice',
                        'score'   => $qData['score'] ?? 1,
                    ]);

                    if (isset($qData['options'])) {
                        foreach ($qData['options'] as $opt) {
                            $question->options()->create([
                                'option_text' => $opt['text'],
                                'is_correct'  => $opt['is_correct'] ?? false,
                            ]);
                        }
                    }
                }
            }

            return $quiz;
        });

        return redirect()->route('teacher.quizzes.edit', $quiz->id)
            ->with('success', 'تم إنشاء الاختبار بنجاح');
    }

    public function edit(Quiz $quiz)
    {
        $course = $quiz->section->course;
        abort_if($course->teacher_id !== auth()->id(), 403, 'لا تملك هذه الدورة');

        $quiz->load('questions.options');
        $sections = $course->sections()->select('id', 'title')->get();

        return view('teacher.quiz-form', compact('course', 'quiz', 'sections'));
    }

    public function update(Request $request, Quiz $quiz)
    {
        $course = $quiz->section->course;
        abort_if($course->teacher_id !== auth()->id(), 403, 'لا تملك هذه الدورة');

        $data = $request->validate([
            'course_section_id' => 'exists:course_sections,id',
            'title'             => 'string|max:255',
            'description'       => 'nullable|string',
            'duration_minutes'  => 'nullable|integer|min:0',
            'pass_mark'         => 'integer|min:1|max:100',
        ]);

        $quiz->update($data);

        return redirect()->route('teacher.quizzes.edit', $quiz->id)
            ->with('success', 'تم تحديث الاختبار');
    }

    public function destroy(Quiz $quiz)
    {
        $course = $quiz->section->course;
        abort_if($course->teacher_id !== auth()->id(), 403);

        $quiz->delete();

        return back()->with('success', 'تم حذف الاختبار');
    }

    // ── Question Management ─────────────────────────────────────────────────

    public function storeQuestion(Request $request, Quiz $quiz)
    {
        $course = $quiz->section->course;
        abort_if($course->teacher_id !== auth()->id(), 403);

        $data = $request->validate([
            'content' => 'required|string',
            'type'    => 'required|in:multiple_choice,true_false,fill_in_blank',
            'score'   => 'required|integer|min:1',
        ]);

        $question = DB::transaction(function () use ($quiz, $data, $request) {
            $question = $quiz->questions()->create($data);

            if ($options = $request->input('options')) {
                foreach ($options as $opt) {
                    $question->options()->create([
                        'option_text' => $opt['text'],
                        'is_correct'  => $opt['is_correct'] ?? false,
                    ]);
                }
            }

            return $question;
        });

        return response()->json(['success' => true, 'data' => $question->load('options')], 201);
    }

    public function updateQuestion(Request $request, Question $question)
    {
        $quiz = $question->quiz;
        $course = $quiz->section->course;
        abort_if($course->teacher_id !== auth()->id(), 403);

        $data = $request->validate([
            'content' => 'string',
            'type'    => 'in:multiple_choice,true_false,fill_in_blank',
            'score'   => 'integer|min:1',
        ]);

        $question->update($data);

        if ($options = $request->input('options')) {
            $question->options()->delete();
            foreach ($options as $opt) {
                $question->options()->create([
                    'option_text' => $opt['text'],
                    'is_correct'  => $opt['is_correct'] ?? false,
                ]);
            }
        }

        return response()->json(['success' => true, 'data' => $question->fresh('options')]);
    }

    public function destroyQuestion(Question $question)
    {
        $quiz = $question->quiz;
        $course = $quiz->section->course;
        abort_if($course->teacher_id !== auth()->id(), 403);

        $question->options()->delete();
        $question->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف السؤال']);
    }

    // ── Statistics ─────────────────────────────────────────────────────────

    public function stats(Quiz $quiz)
    {
        $course = $quiz->section->course;
        abort_if($course->teacher_id !== auth()->id(), 403);

        $quiz->loadCount('attempts');
        $totalAttempts = $quiz->attempts_count;
        $passedAttempts = $quiz->attempts()->where('passed', true)->count();
        $averageScore = $quiz->attempts()->avg(DB::raw('(total_score * 100.0 / NULLIF(max_score, 0))'));

        $attempts = $quiz->attempts()
            ->with('user:id,name')
            ->latest()
            ->paginate(20);

        return view('teacher.quiz-stats', compact('quiz', 'course', 'totalAttempts', 'passedAttempts', 'averageScore', 'attempts'));
    }
}
