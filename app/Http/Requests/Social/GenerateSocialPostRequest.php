<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class GenerateSocialPostRequest extends FormRequest
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

        if ($this->input('source_url') === '') {
            $this->merge(['source_url' => null]);
        }

        if (! $this->filled('image_instructions') && $this->filled('user_image_instructions')) {
            $this->merge(['image_instructions' => $this->input('user_image_instructions')]);
        }

        if ($this->input('image_instructions') === '') {
            $this->merge(['image_instructions' => null]);
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
            'user_prompt' => ['required', 'string', 'max:5000'],
            'source_url' => ['nullable', 'url', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif,webp', 'max:5120'],
            'enable_web_search' => ['sometimes', 'boolean'],
            'tone' => ['nullable', 'string', 'max:50'],
            'image_instructions' => ['nullable', 'string', 'max:5000'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['in:facebook,instagram'],
            'timezone' => ['nullable', 'timezone'],
        ];
    }
}
