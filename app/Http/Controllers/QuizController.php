<?php

namespace App\Http\Controllers;

use App\Models\Quiz;
use App\Models\Question;
use App\Models\QuizAttempt;
use App\Services\GamificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuizController extends Controller
{
    // Get Quiz Information for taking the quiz
    public function show(Request $request, $id)
    {
        $quiz = Quiz::with(['questions.options', 'section.course'])->findOrFail($id);
        
        // Authorization: Check if user is enrolled in the course
        $user = $request->user();
        if ($user && $user->role !== 'admin' && $user->role !== 'teacher') {
             // Simple enrollment check
             $isEnrolled = DB::table('course_enrollments')
                 ->where('student_id', $user->id)
                 ->where('course_id', $quiz->section->course_id)
                 ->exists();
             
             if (!$isEnrolled) {
                 return response()->json(['message' => 'Not enrolled'], 403);
             }
        }

        // Hide 'is_correct' from output so user can't cheat during attempt
        $quiz->questions->each(function ($question) {
            $question->options->makeHidden(['is_correct', 'created_at', 'updated_at']);
        });

        return response()->json([
            'success' => true,
            'data' => $quiz
        ]);
    }

    // Submit Quiz Answers
    public function submit(Request $request, $id)
    {
        $user = $request->user();
        $quiz = Quiz::with(['questions.options'])->findOrFail($id);

        $answers = $request->input('answers'); // Format: [{question_id: 1, option_id: 2}, ...]

        if (!is_array($answers)) {
            return response()->json(['message' => 'Invalid answers format'], 422);
        }

        $totalScore = 0;
        $maxScore = $quiz->questions->sum('score');
        $attemptAnswers = [];

        foreach ($answers as $ans) {
            $questionId = $ans['question_id'] ?? null;
            $optionId = $ans['option_id'] ?? null; 
            
            $question = $quiz->questions->where('id', $questionId)->first();
            if (!$question) continue;

            $isCorrect = false;
            // Support Multiple Choice and True/False
            if ($optionId) {
                $selectedOption = $question->options->where('id', $optionId)->first();
                if ($selectedOption && $selectedOption->is_correct) {
                    $isCorrect = true;
                    $totalScore += $question->score;
                }
            }

            // Prepare snapshot for attempt details
            $attemptAnswers[] = [
                'question_id' => $question->id,
                'question_option_id' => $optionId,
                'text_answer' => $ans['text_answer'] ?? null,
                'is_correct' => $isCorrect
            ];
        }

        $passed = $maxScore > 0 ? (($totalScore / $maxScore) * 100 >= $quiz->pass_mark) : true;

        DB::beginTransaction();
        try {
            $attempt = QuizAttempt::create([
                'user_id' => $user->id,
                'quiz_id' => $quiz->id,
                'total_score' => $totalScore,
                'max_score' => $maxScore,
                'passed' => $passed,
                'started_at' => $request->input('started_at', now()), // Client should send start time or we assume now - duration
                'completed_at' => now(),
            ]);
            
            // Batch insert answers
            $now = now();
            $answerRecords = array_map(function($a) use ($attempt, $now) {
                return array_merge($a, [
                    'quiz_attempt_id' => $attempt->id, 
                    'created_at' => $now, 
                    'updated_at' => $now
                ]);
            }, $attemptAnswers);
            
            DB::table('quiz_attempt_answers')->insert($answerRecords);

            DB::commit();

            // ── Gamification hook ─────────────────────────────────────────────
            if ($passed) {
                $scorePercent = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 100;
                app(GamificationService::class)->onQuizPassed($user, $scorePercent);
            }
            // ─────────────────────────────────────────────────────────────────

            return response()->json([
                'success' => true,
                'data'    => [
                    'score'      => $totalScore,
                    'max_score'  => $maxScore,
                    'passed'     => $passed,
                    'attempt_id' => $attempt->id,
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Failed to submit quiz: ' . $e->getMessage()], 500);
        }
    }

    // Get Attempt Results (Review)
    public function results(Request $request, $attemptId)
    {
        $user = $request->user();
        $attempt = QuizAttempt::with(['quiz.questions.options'])
            ->where('id', $attemptId)
            ->firstOrFail();

        if ($attempt->user_id != $user->id && $user->role !== 'admin' && $user->role !== 'teacher') {
             return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Fetch detailed answers
        $userAnswers = DB::table('quiz_attempt_answers')
            ->where('quiz_attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        // Attach user answer to question structure for frontend
        $attempt->quiz->questions->each(function($q) use ($userAnswers) {
            $ans = $userAnswers->get($q->id);
            $q->user_answer_option_id = $ans ? $ans->question_option_id : null;
            $q->user_is_correct = $ans ? $ans->is_correct : false;
        });

        return response()->json([
             'success' => true,
             'data' => $attempt
        ]);
    }
}
