<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutDay;
use Illuminate\Foundation\Http\FormRequest;

class StoreWorkoutDayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkoutDay::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'workout_plan_id' => ['required', 'exists:workout_plans,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'order' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
