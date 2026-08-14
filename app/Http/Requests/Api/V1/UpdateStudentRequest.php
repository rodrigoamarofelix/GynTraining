<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProfileStatus;
use App\Http\Requests\Concerns\ValidatesManagedGym;
use App\Models\Trainer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateStudentRequest extends FormRequest
{
    use ValidatesManagedGym;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('student')) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('student')?->user_id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gym_id' => ['sometimes', 'exists:gyms,id'],
            'trainer_id' => ['nullable', 'exists:trainers,id'],
            'birth_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', Rule::in(array_column(ProfileStatus::cases(), 'value'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var \App\Models\Student|null $student */
            $student = $this->route('student');

            if (! $student) {
                return;
            }

            $newStatus = $this->input('status');

            if ($newStatus === ProfileStatus::Active->value
                && $student->status === ProfileStatus::Pending
                && ! $this->filled('trainer_id')) {
                $validator->errors()->add('trainer_id', 'Selecione o professor responsável.');
            }

            if ($this->filled('trainer_id')) {
                $trainer = Trainer::query()->find($this->input('trainer_id'));
                $gymId = (int) $this->input('gym_id', $student->gym_id);

                if ($trainer && $trainer->gym_id !== $gymId) {
                    $validator->errors()->add('trainer_id', 'O professor deve pertencer à mesma academia.');
                }
            }

            if ($this->filled('gym_id')) {
                $this->ensureManagedGym($validator, (int) $this->input('gym_id'));
            }
        });
    }
}
