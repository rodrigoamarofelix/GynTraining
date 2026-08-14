<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutSession;
use Illuminate\Foundation\Http\FormRequest;

class FinishWorkoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->route('workout_session');

        return $session instanceof WorkoutSession
            && ($this->user()?->can('finish', $session) ?? false);
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
