<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\GoalStatus;
use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGoalRequest extends FormRequest
{
    public function authorize(): bool
    {
        $goal = $this->route('goal');

        return $goal instanceof Goal
            && ($this->user()?->can('update', $goal) ?? false);
    }

    public function rules(): array
    {
        return [
            'exercise_id' => ['nullable', 'exists:exercises,id'],
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target' => ['sometimes', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', Rule::in(array_column(GoalStatus::cases(), 'value'))],
        ];
    }
}
