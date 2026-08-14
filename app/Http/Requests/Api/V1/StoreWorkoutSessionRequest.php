<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutSession;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $session = $this->resolveSession();

        return $session !== null
            && ($this->user()?->can('logSet', $session) ?? false);
    }

    public function rules(): array
    {
        return [
            'workout_session_id' => ['required_without:workout_session', 'integer', 'exists:workout_sessions,id'],
            'exercise_id' => ['required', 'integer', 'exists:exercises,id'],
            'workout_exercise_id' => ['nullable', 'integer', 'exists:workout_exercises,id'],
            'set_number' => ['required', 'integer', 'min:1'],
            'repetitions' => ['nullable', 'integer', 'min:0'],
            'load' => ['nullable', 'numeric', 'min:0'],
            'rest_time' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $session = $this->route('workout_session');

        if ($session instanceof WorkoutSession) {
            $this->merge(['workout_session_id' => $session->id]);
        }
    }

    private function resolveSession(): ?WorkoutSession
    {
        $session = $this->route('workout_session');

        if ($session instanceof WorkoutSession) {
            return $session;
        }

        $sessionId = $this->input('workout_session_id');

        return $sessionId ? WorkoutSession::query()->find($sessionId) : null;
    }
}
