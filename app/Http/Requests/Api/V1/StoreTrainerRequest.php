<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProfileStatus;
use App\Http\Requests\Concerns\ValidatesManagedGym;
use App\Models\Trainer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreTrainerRequest extends FormRequest
{
    use ValidatesManagedGym;

    public function authorize(): bool
    {
        return $this->user()?->can('create', Trainer::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'phone' => ['nullable', 'string', 'max:20'],
            'gym_id' => ['required', 'exists:gyms,id'],
            'bio' => ['nullable', 'string'],
            'specialty' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_column(ProfileStatus::cases(), 'value'))],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->ensureManagedGym($validator, (int) $this->input('gym_id'));
        });
    }
}
