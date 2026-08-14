<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('workout_exercise')) ?? false;
    }

    public function rules(): array
    {
        return [
            'exercise_id' => ['sometimes', 'exists:exercises,id'],
            'order' => ['sometimes', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'integer', 'min:0'],
            'rest_time' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
