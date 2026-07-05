<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $course = $this->route('course');
        // If course is bound implicitly, check ownership. If not, controller handles it or we can check via ID.
        // Returning true for now and letting the controller check ownership for simplicity, as ID might not be auto-resolved to model here.
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category_id' => 'sometimes|required|exists:categories,id',
            'price' => 'sometimes|required|numeric|min:0',
            'level' => 'sometimes|required|in:beginner,intermediate,advanced',
            'status' => 'sometimes|in:draft,published,pending',
            'language' => 'nullable|string|max:10',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
            'video_intro' => 'nullable|mimetypes:video/mp4,video/avi,video/mpeg,video/quicktime|max:51200',
        ];
    }
}
