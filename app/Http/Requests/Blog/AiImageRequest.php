<?php

namespace App\Http\Requests\Blog;

use Illuminate\Foundation\Http\FormRequest;

class AiImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target' => ['required', 'string', 'in:image,image2'],
            'instruction' => ['nullable', 'string', 'max:2000'],
            'title' => ['nullable', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:100000'],
            'content' => ['nullable', 'string', 'max:100000'],
            'current_path' => ['nullable', 'string', 'max:255'],
            'previous_path' => ['nullable', 'string', 'max:255'],
            'saved_path' => ['nullable', 'string', 'max:255'],
        ];
    }
}
