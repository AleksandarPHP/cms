<?php

namespace App\Http\Requests\Social;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePlanPostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'post_title' => ['nullable', 'string', 'max:120'],
            'facebook' => ['nullable', 'string'],
            'instagram' => ['nullable', 'string'],
            'hashtags' => ['nullable', 'array'],
            'hashtags.*' => ['string', 'max:100'],
            'generated_image_prompt' => ['nullable', 'string', 'max:8000'],
            'scheduled_at' => ['nullable', 'date_format:Y-m-d H:i'],
            'timezone' => ['nullable', 'timezone'],
        ];
    }
}
