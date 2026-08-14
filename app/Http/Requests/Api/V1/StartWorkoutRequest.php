<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutPlan;
use Illuminate\Foundation\Http\FormRequest;

class StartWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workout = $this->route('workout');

        return $workout instanceof WorkoutPlan
            && ($this->user()?->can('view', $workout) ?? false)
            && ($this->user()?->can('start', \App\Models\WorkoutSession::class) ?? false);
    }

    public function rules(): array
    {
        $workout = $this->route('workout');
        $planId = $workout instanceof WorkoutPlan ? $workout->id : null;

        return [
            'workout_day_id' => [
                'required',
                'integer',
                'exists:workout_days,id,workout_plan_id,'.$planId,
            ],
        ];
    }
}
