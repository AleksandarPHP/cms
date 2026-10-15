<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class RegenerateSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('user_prompt') && $this->filled('prompt')) {
            $this->merge(['user_prompt' => $this->input('prompt')]);
        }

        if ($this->filled('note') && ! $this->filled('instruction')) {
            $this->merge(['instruction' => $this->input('note')]);
        }

        if ($this->input('source_url') === '') {
            $this->merge(['source_url' => null]);
        }

        if ($this->exists('enable_web_search')) {
            $this->merge([
                'enable_web_search' => filter_var($this->input('enable_web_search'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'user_prompt' => ['nullable', 'string', 'max:5000', 'required_without:instruction'],
            'instruction' => ['nullable', 'string', 'max:2000', 'required_without:user_prompt'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'enable_web_search' => ['sometimes', 'boolean'],
            'tone' => ['nullable', 'string', 'max:50'],
            'image_instructions' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
