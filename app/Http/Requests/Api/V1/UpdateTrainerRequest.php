<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProfileStatus;
use App\Http\Requests\Concerns\ValidatesManagedGym;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdateTrainerRequest extends FormRequest
{
    use ValidatesManagedGym;

    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('trainer')) ?? false;
    }

    public function rules(): array
    {
        $userId = $this->route('trainer')?->user_id;

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['nullable', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gym_id' => ['sometimes', 'exists:gyms,id'],
            'bio' => ['nullable', 'string'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(array_column(ProfileStatus::cases(), 'value'))],
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
