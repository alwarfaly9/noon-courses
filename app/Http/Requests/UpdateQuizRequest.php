<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');
        return $this->user()
            && $this->user()->hasRoleName('teacher')
            && $quiz->section->course->teacher_id === $this->user()->id;
    }

    public function rules(): array
    {
        return [
            'course_section_id' => 'exists:course_sections,id',
            'title'             => 'string|max:255',
            'description'       => 'nullable|string',
            'duration_minutes'  => 'nullable|integer|min:0',
            'pass_mark'         => 'integer|min:1|max:100',
        ];
    }
}
