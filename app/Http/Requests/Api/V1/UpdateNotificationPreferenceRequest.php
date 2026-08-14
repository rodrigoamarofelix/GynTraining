<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNotificationPreferenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'database_enabled' => ['sometimes', 'boolean'],
            'mail_enabled' => ['sometimes', 'boolean'],
            'push_enabled' => ['sometimes', 'boolean'],
            'workout_reminders' => ['sometimes', 'boolean'],
            'workout_updates' => ['sometimes', 'boolean'],
            'inactivity_alerts' => ['sometimes', 'boolean'],
            'reminder_time' => ['sometimes', 'date_format:H:i'],
        ];
    }
}
