<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ExerciseDifficulty;
use App\Enums\ExerciseStatus;
use App\Enums\RoleName;
use App\Http\Requests\Concerns\ValidatesManagedGym;
use App\Models\Exercise;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreExerciseRequest extends FormRequest
{
    use ValidatesManagedGym;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Exercise::class) ?? false;
    }

    public function rules(): array
    {
        $gymIdRules = ['nullable', 'exists:gyms,id'];

        if ($this->user()?->hasRole(RoleName::GymAdmin)) {
            $gymIdRules = ['required', 'exists:gyms,id'];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'instructions' => ['nullable', 'string'],
            'exercise_category_id' => ['required', 'exists:exercise_categories,id'],
            'muscle_group_id' => ['required', 'exists:muscle_groups,id'],
            'gym_id' => $gymIdRules,
            'equipment' => ['nullable', 'string', 'max:255'],
            'difficulty' => ['nullable', 'string', Rule::in(array_column(ExerciseDifficulty::cases(), 'value'))],
            'video_url' => ['nullable', 'url', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_column(ExerciseStatus::cases(), 'value'))],
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
