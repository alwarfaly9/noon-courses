<?php

namespace App\Services;

use App\Models\Quiz;
use App\Models\QuizAttempt;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuizGradingService
{
    public function grade(Quiz $quiz, User $user, array $answers, ?string $startedAt = null): QuizAttempt
    {
        $quiz->load(['questions.options']);

        $totalScore = 0;
        $maxScore = $quiz->questions->sum('score');
        $attemptAnswers = [];

        foreach ($answers as $ans) {
            $questionId = $ans['question_id'] ?? null;
            $optionId = $ans['option_id'] ?? null;

            $question = $quiz->questions->where('id', $questionId)->first();
            if (!$question) continue;

            $isCorrect = false;
            if ($optionId) {
                $selectedOption = $question->options->where('id', $optionId)->first();
                if ($selectedOption && $selectedOption->is_correct) {
                    $isCorrect = true;
                    $totalScore += $question->score;
                }
            }

            $attemptAnswers[] = [
                'question_id'         => $question->id,
                'question_option_id'  => $optionId,
                'text_answer'         => $ans['text_answer'] ?? null,
                'is_correct'          => $isCorrect,
            ];
        }

        $passed = $maxScore > 0
            ? (($totalScore / $maxScore) * 100 >= $quiz->pass_mark)
            : true;

        return DB::transaction(function () use ($quiz, $user, $totalScore, $maxScore, $passed, $startedAt, $attemptAnswers) {
            $attempt = QuizAttempt::create([
                'user_id'      => $user->id,
                'quiz_id'      => $quiz->id,
                'total_score'  => $totalScore,
                'max_score'    => $maxScore,
                'passed'       => $passed,
                'started_at'   => $startedAt ?? now(),
                'completed_at' => now(),
            ]);

            $now = now();
            $answerRecords = array_map(function ($a) use ($attempt, $now) {
                return array_merge($a, [
                    'quiz_attempt_id' => $attempt->id,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ]);
            }, $attemptAnswers);

            DB::table('quiz_attempt_answers')->insert($answerRecords);

            return $attempt;
        });
    }

    public function getResults(QuizAttempt $attempt): QuizAttempt
    {
        $attempt->load(['quiz.questions.options']);
        $userAnswers = DB::table('quiz_attempt_answers')
            ->where('quiz_attempt_id', $attempt->id)
            ->get()
            ->keyBy('question_id');

        $attempt->quiz->questions->each(function ($q) use ($userAnswers) {
            $ans = $userAnswers->get($q->id);
            $q->user_answer_option_id = $ans ? $ans->question_option_id : null;
            $q->user_is_correct = $ans ? $ans->is_correct : false;
        });

        return $attempt;
    }
}
