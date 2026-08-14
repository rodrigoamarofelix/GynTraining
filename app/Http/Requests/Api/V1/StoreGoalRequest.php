<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\GoalStatus;
use App\Http\Requests\Concerns\EnsuresOwnStudentId;
use App\Models\Goal;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreGoalRequest extends FormRequest
{
    use EnsuresOwnStudentId;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Goal::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'exercise_id' => ['nullable', 'exists:exercises,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target' => ['required', 'numeric', 'min:0'],
            'current_value' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:50'],
            'start_date' => ['nullable', 'date'],
            'target_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', 'string', Rule::in(array_column(GoalStatus::cases(), 'value'))],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeOwnStudentIdIfMissing();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(fn (Validator $validator) => $this->ensureOwnStudentId($validator));
    }
}
