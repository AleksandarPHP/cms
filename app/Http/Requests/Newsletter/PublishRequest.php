<?php

namespace App\Http\Requests\Newsletter;

use App\Models\Newsletter;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class PublishRequest extends FormRequest
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
            'mode' => ['required', 'in:now,schedule'],
            'scheduled_at' => [
                'exclude_unless:mode,schedule',
                'required',
                'date_format:Y-m-d\TH:i',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value)) {
                        return;
                    }

                    $moment = Carbon::createFromFormat('Y-m-d\TH:i', $value, Newsletter::TIMEZONE);

                    if ($moment === false || $moment->lessThanOrEqualTo(now())) {
                        $fail(__('app.newsletter_schedule_future'));
                    }
                },
            ],
        ];
    }

    protected function prepareForValidation(): void
    {
        $value = (string) $this->input('scheduled_at', '');

        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2})/', $value, $match)) {
            $this->merge(['scheduled_at' => $match[1]]);
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'scheduled_at' => __('app.newsletter_scheduled_at'),
        ];
    }
}
