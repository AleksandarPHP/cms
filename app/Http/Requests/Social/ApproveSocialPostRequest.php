<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class ApproveSocialPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->exists('publish_now')) {
            $this->merge([
                'publish_now' => filter_var($this->input('publish_now'), FILTER_VALIDATE_BOOLEAN),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'fb_content' => ['nullable', 'string'],
            'ig_content' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:100'],
            'scheduled_at' => ['nullable', 'date'],
            'publish_now' => ['sometimes', 'boolean'],
            'timezone' => ['nullable', 'timezone'],
            'generated_image_prompt' => ['nullable', 'string', 'max:8000'],
        ];
    }
}
