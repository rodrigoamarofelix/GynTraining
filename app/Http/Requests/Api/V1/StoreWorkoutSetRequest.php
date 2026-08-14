<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutSet;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkoutSet::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'workout_exercise_id' => ['required', 'exists:workout_exercises,id'],
            'set_number' => ['required', 'integer', 'min:1'],
            'repetitions' => ['nullable', 'integer', 'min:0'],
            'load' => ['nullable', 'numeric', 'min:0'],
            'rest_time' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
