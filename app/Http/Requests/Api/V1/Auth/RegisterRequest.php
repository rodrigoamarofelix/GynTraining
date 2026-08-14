<?php

namespace App\Http\Requests\Api\V1\Auth;

use App\Enums\RoleName;
use App\Services\Auth\GymLoginGuard;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gym_id' => ['required', 'exists:gyms,id'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'role' => RoleName::Student->value,
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('gym_id')) {
                return;
            }

            try {
                app(GymLoginGuard::class)->ensureGymAcceptsRegistration((int) $this->input('gym_id'));
            } catch (\Illuminate\Validation\ValidationException $exception) {
                foreach ($exception->errors() as $field => $messages) {
                    foreach ($messages as $message) {
                        $validator->errors()->add($field, $message);
                    }
                }
            }
        });
    }
}
