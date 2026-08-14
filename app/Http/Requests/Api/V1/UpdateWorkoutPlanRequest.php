<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\WorkoutPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWorkoutPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('workout')) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['sometimes', 'exists:students,id'],
            'trainer_id' => ['sometimes', 'exists:trainers,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['sometimes', 'string', Rule::in(array_column(WorkoutPlanStatus::cases(), 'value'))],
            'days' => ['nullable', 'array'],
            'days.*.name' => ['required_with:days', 'string', 'max:255'],
            'days.*.description' => ['nullable', 'string'],
            'days.*.order' => ['nullable', 'integer', 'min:1'],
            'days.*.exercises' => ['nullable', 'array'],
            'days.*.exercises.*.exercise_id' => ['required', 'exists:exercises,id'],
            'days.*.exercises.*.order' => ['nullable', 'integer', 'min:1'],
            'days.*.exercises.*.notes' => ['nullable', 'string'],
            'days.*.exercises.*.execution_time' => ['nullable', 'integer', 'min:0'],
            'days.*.exercises.*.rest_time' => ['nullable', 'integer', 'min:0'],
            'days.*.exercises.*.sets' => ['nullable', 'array'],
            'days.*.exercises.*.sets.*.set_number' => ['required', 'integer', 'min:1'],
            'days.*.exercises.*.sets.*.repetitions' => ['nullable', 'integer', 'min:0'],
            'days.*.exercises.*.sets.*.load' => ['nullable', 'numeric', 'min:0'],
            'days.*.exercises.*.sets.*.rest_time' => ['nullable', 'integer', 'min:0'],
            'days.*.exercises.*.sets.*.duration' => ['nullable', 'integer', 'min:0'],
            'days.*.exercises.*.sets.*.notes' => ['nullable', 'string'],
        ];
    }
}
