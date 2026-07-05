<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreChatRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller via authorizeAccess
    }

    public function rules(): array
    {
        return [
            'body'  => 'nullable|string|max:5000',
            'image' => 'nullable|image|max:10240', // 10MB limit
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!$this->filled('body') && !$this->hasFile('image')) {
                $validator->errors()->add('body', 'Message must have a body or an image');
            }
        });
    }
}
