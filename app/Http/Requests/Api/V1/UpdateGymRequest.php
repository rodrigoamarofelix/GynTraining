<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\GymStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGymRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('gym')) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', 'string', Rule::in(array_column(GymStatus::cases(), 'value'))],
        ];
    }
}
