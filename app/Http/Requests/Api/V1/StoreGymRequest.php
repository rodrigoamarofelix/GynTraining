<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\GymStatus;
use App\Models\Gym;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGymRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Gym::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::in(array_column(GymStatus::cases(), 'value'))],
        ];
    }
}
