<?php

namespace App\Http\Requests\Newsletter;

use Illuminate\Foundation\Http\FormRequest;

class SelectBlogsRequest extends FormRequest
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
            'blog_ids' => ['required', 'array', 'min:1'],
            'blog_ids.*' => ['required', 'integer', 'distinct', 'exists:blogs,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'blog_ids.required' => __('app.newsletter_need_blogs'),
            'blog_ids.min' => __('app.newsletter_need_blogs'),
        ];
    }
}
