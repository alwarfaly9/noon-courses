<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'teacher_id' => 'required|exists:users,id',
            'category_id' => 'required|exists:categories,id',
            'description' => 'required|string',
            'short_description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lte:price',
            'requirements_text' => 'nullable|string',
            'learn_text' => 'nullable|string',
            'level' => 'required|in:beginner,intermediate,advanced',
            'language' => 'required|in:ar,en',
            'status' => 'nullable|in:draft,pending,published,rejected',
        ];
    }
}
