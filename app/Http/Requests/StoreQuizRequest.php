<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() && $this->user()->hasRoleName('teacher');
    }

    public function rules(): array
    {
        return [
            'course_id'         => 'required|exists:courses,id',
            'course_section_id' => 'required|exists:course_sections,id',
            'title'             => 'required|string|max:255',
            'description'       => 'nullable|string',
            'duration_minutes'  => 'nullable|integer|min:0',
            'pass_mark'         => 'required|integer|min:1|max:100',
            'questions'         => 'nullable|array',
            'questions.*.content' => 'required_with:questions|string',
            'questions.*.type'    => 'nullable|in:multiple_choice,true_false,fill_in_blank',
            'questions.*.score'   => 'nullable|integer|min:1',
            'questions.*.options' => 'nullable|array',
            'questions.*.options.*.text'       => 'required_with:questions.*.options|string',
            'questions.*.options.*.is_correct' => 'nullable|boolean',
        ];
    }
}
