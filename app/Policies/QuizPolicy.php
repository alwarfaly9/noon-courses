<?php

namespace App\Policies;

use App\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function viewAnyTeacher(User $user, Quiz $quiz): bool
    {
        return $user->hasRoleName('teacher') && $quiz->section->course->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->hasRoleName('teacher');
    }

    public function view(User $user, Quiz $quiz): bool
    {
        if ($user->hasRoleName('teacher')) {
            return $quiz->section->course->teacher_id === $user->id;
        }
        return $user->hasRoleName('student');
    }

    public function update(User $user, Quiz $quiz): bool
    {
        return $user->hasRoleName('teacher') && $quiz->section->course->teacher_id === $user->id;
    }

    public function delete(User $user, Quiz $quiz): bool
    {
        return $user->hasRoleName('teacher') && $quiz->section->course->teacher_id === $user->id;
    }

    public function submit(User $user, Quiz $quiz): bool
    {
        return $user->hasRoleName('student');
    }
}
