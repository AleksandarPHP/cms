<?php

namespace App\Http\Requests\Blog;

use App\Services\BlogAiService;
use Illuminate\Foundation\Http\FormRequest;

class AiTextRequest extends FormRequest
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
            'instruction' => ['required', 'string', 'max:2000'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', 'distinct', 'in:'.implode(',', BlogAiService::FIELDS)],
            'meta_title' => ['nullable', 'string', 'max:191'],
            'meta_description' => ['nullable', 'string', 'max:5000'],
            'title' => ['nullable', 'string', 'max:191'],
            'subtitle' => ['nullable', 'string', 'max:191'],
            'description' => ['nullable', 'string', 'max:100000'],
            'content' => ['nullable', 'string', 'max:100000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $fields = $this->input('fields', []);

        if (! is_array($fields)) {
            return;
        }

        $this->merge([
            'fields' => array_values(array_unique(array_map('strval', $fields))),
        ]);
    }
}
