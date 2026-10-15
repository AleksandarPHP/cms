<?php

namespace App\Http\Requests\Social;

use App\Models\ContentPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreContentPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $days = [];

        foreach ((array) $this->input('publish_days', []) as $day) {
            $day = strtolower(trim((string) $day));

            if ($day !== '' && ! in_array($day, $days, true)) {
                $days[] = $day;
            }
        }

        $types = [];

        foreach ((array) $this->input('day_post_types', []) as $day => $type) {
            $day = strtolower(trim((string) $day));
            $type = strtolower(trim((string) $type));

            if (! in_array($day, $days, true) || $type === '') {
                continue;
            }

            $types[$day] = $type;
        }

        $time = (string) $this->input('publish_time');

        $this->merge([
            'publish_days' => $days,
            'day_post_types' => $types,
            'publish_time' => preg_match('/^\d{2}:\d{2}/', $time) ? substr($time, 0, 5) : $time,
            'enable_web_search' => $this->boolean('enable_web_search'),
        ]);
    }

    public function rules(): array
    {
        return [
            'topic' => ['required', 'string', 'min:3', 'max:5000'],
            'duration_months' => ['required', 'integer', 'in:1,3,6'],
            'posts_per_week' => ['required', 'integer', 'between:1,7'],
            'publish_days' => ['nullable', 'array'],
            'publish_days.*' => ['in:'.implode(',', array_keys(ContentPlan::WEEKDAYS))],
            'day_post_types' => ['nullable', 'array'],
            'day_post_types.*' => ['string', Rule::in(array_keys(ContentPlan::POST_TYPES))],
            'publish_time' => ['required', 'date_format:H:i'],
            'tone' => ['nullable', 'string', 'max:50'],
            'platforms' => ['nullable', 'array'],
            'platforms.*' => ['in:facebook,instagram'],
            'timezone' => ['nullable', 'timezone'],
            'enable_web_search' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            $days = (array) $this->input('publish_days', []);
            $types = (array) $this->input('day_post_types', []);

            foreach ($days as $day) {
                if (! isset($types[$day]) || $types[$day] === '') {
                    $validator->errors()->add(
                        'day_post_types.'.$day,
                        'Choose a post type for '.(ContentPlan::WEEKDAYS[$day] ?? ucfirst((string) $day)).'.',
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'day_post_types.*.in' => 'Choose a valid post type for each selected day.',
        ];
    }
}
