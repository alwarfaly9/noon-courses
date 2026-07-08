<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Quiz;
use App\Models\QuizAttempt;
use Illuminate\Support\Facades\DB;

class QuizService
{
    public function getCourseQuizzes(Course $course): mixed
    {
        return Quiz::where('course_id', $course->id)
            ->withCount('attempts', 'questions')
            ->get();
    }

    public function createQuiz(array $data, array $questions = []): Quiz
    {
        return DB::transaction(function () use ($data, $questions) {
            $quiz = Quiz::create($data);

            foreach ($questions as $qData) {
                $question = $quiz->questions()->create([
                    'content'     => $qData['content'],
                    'type'        => $qData['type'] ?? 'multiple_choice',
                    'score'       => $qData['score'] ?? 1,
                    'explanation' => $qData['explanation'] ?? null,
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

            return $quiz->fresh(['questions.options']);
        });
    }

    public function updateQuiz(Quiz $quiz, array $data): Quiz
    {
        $quiz->update($data);
        return $quiz->fresh();
    }

    public function deleteQuiz(Quiz $quiz): void
    {
        DB::transaction(function () use ($quiz) {
            foreach ($quiz->questions as $question) {
                $question->options()->delete();
            }
            $quiz->questions()->delete();
            $quiz->attempts()->delete();
            $quiz->delete();
        });
    }

    public function addQuestion(Quiz $quiz, array $data, array $options = []): Question
    {
        return DB::transaction(function () use ($quiz, $data, $options) {
            $question = $quiz->questions()->create([
                'content'     => $data['content'],
                'type'        => $data['type'] ?? 'multiple_choice',
                'score'       => $data['score'] ?? 1,
                'explanation' => $data['explanation'] ?? null,
            ]);

            foreach ($options as $opt) {
                $question->options()->create([
                    'option_text' => $opt['text'],
                    'is_correct'  => $opt['is_correct'] ?? false,
                ]);
            }

            return $question->load('options');
        });
    }

    public function updateQuestion(Question $question, array $data, array $options = []): Question
    {
        return DB::transaction(function () use ($question, $data, $options) {
            $question->update($data);

            if ($options) {
                $question->options()->delete();
                foreach ($options as $opt) {
                    $question->options()->create([
                        'option_text' => $opt['text'],
                        'is_correct'  => $opt['is_correct'] ?? false,
                    ]);
                }
            }

            return $question->fresh('options');
        });
    }

    public function deleteQuestion(Question $question): void
    {
        $question->options()->delete();
        $question->delete();
    }

    public function getQuizWithResults(Quiz $quiz): mixed
    {
        $quiz->loadCount('attempts');
        $totalAttempts = $quiz->attempts_count;
        $passedAttempts = $quiz->attempts()->where('passed', true)->count();
        $averageScore = $quiz->attempts()->avg(DB::raw('(total_score * 100.0 / NULLIF(max_score, 0))'));

        $attempts = $quiz->attempts()
            ->with('user:id,name')
            ->latest()
            ->paginate(20);

        return compact('quiz', 'totalAttempts', 'passedAttempts', 'averageScore', 'attempts');
    }

    public function getQuizForStudent(Quiz $quiz): Quiz
    {
        $quiz->load(['questions.options' => function ($q) {
            $q->orderBy('id');
        }]);

        $quiz->questions->each(function ($question) {
            $question->options->makeHidden(['is_correct', 'created_at', 'updated_at']);
        });

        return $quiz;
    }
}
