<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'answers'          => 'required|array|min:1',
            'answers.*.question_id' => 'required|integer|exists:questions,id',
            'answers.*.option_id'   => 'nullable|integer|exists:question_options,id',
            'answers.*.text_answer' => 'nullable|string',
            'started_at'       => 'nullable|date',
        ];
    }
}
