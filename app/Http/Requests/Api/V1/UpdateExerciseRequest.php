<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ExerciseDifficulty;
use App\Enums\ExerciseStatus;
use App\Http\Requests\Concerns\ValidatesManagedGym;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateExerciseRequest extends FormRequest
{
    use ValidatesManagedGym;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('exercise')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'exercise_category_id' => ['sometimes', 'exists:exercise_categories,id'],
            'muscle_group_id' => ['sometimes', 'exists:muscle_groups,id'],
            'gym_id' => ['nullable', 'exists:gyms,id'],
            'equipment' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['sometimes', 'string', Rule::in(array_column(ExerciseDifficulty::cases(), 'value'))],
            'video_url' => ['nullable', 'url', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(array_column(ExerciseStatus::cases(), 'value'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->filled('gym_id')) {
                $this->ensureManagedGym($validator, (int) $this->input('gym_id'));
            }
        });
    }
}
