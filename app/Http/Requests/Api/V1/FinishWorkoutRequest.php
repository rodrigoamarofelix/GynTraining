<?php

namespace App\Http\Requests\Api\V1;

use App\Models\WorkoutPlan;
use Illuminate\Foundation\Http\FormRequest;

class FinishWorkoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        $workout = $this->route('workout');

        return $workout instanceof WorkoutPlan
            && ($this->user()?->can('view', $workout) ?? false);
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
        ];
    }
}
