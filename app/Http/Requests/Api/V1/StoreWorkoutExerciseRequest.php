<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutExercise;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutExerciseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkoutExercise::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'workout_day_id' => ['required', 'exists:workout_days,id'],
            'exercise_id' => ['required', 'exists:exercises,id'],
            'order' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
            'execution_time' => ['nullable', 'integer', 'min:0'],
            'rest_time' => ['nullable', 'integer', 'min:0'],
            'sets' => ['nullable', 'array'],
            'sets.*.set_number' => ['required', 'integer', 'min:1'],
            'sets.*.repetitions' => ['nullable', 'integer', 'min:0'],
            'sets.*.load' => ['nullable', 'numeric', 'min:0'],
            'sets.*.rest_time' => ['nullable', 'integer', 'min:0'],
            'sets.*.duration' => ['nullable', 'integer', 'min:0'],
            'sets.*.notes' => ['nullable', 'string'],
        ];
    }
}
