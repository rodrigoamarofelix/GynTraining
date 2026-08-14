<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\RoleName;
use App\Enums\WorkoutPlanStatus;
use App\Models\Student;
use App\Models\WorkoutPlan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWorkoutPlanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', WorkoutPlan::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'trainer_id' => ['nullable', 'exists:trainers,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', Rule::in(array_column(WorkoutPlanStatus::cases(), 'value'))],
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

    protected function prepareForValidation(): void
    {
        if ($this->user()?->trainer && ! $this->filled('trainer_id')) {
            $this->merge(['trainer_id' => $this->user()->trainer->id]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $trainer = $this->user()?->trainer;

            if (! $trainer || ! $this->user()?->hasRole(RoleName::Trainer)) {
                return;
            }

            $student = Student::query()->find($this->input('student_id'));

            if (! $student) {
                return;
            }

            if ($student->gym_id !== $trainer->gym_id) {
                $validator->errors()->add('student_id', 'O aluno deve pertencer à sua academia.');
            }

            if ($student->trainer_id && $student->trainer_id !== $trainer->id) {
                $validator->errors()->add('student_id', 'Este aluno já está vinculado a outro professor.');
            }
        });
    }
}
