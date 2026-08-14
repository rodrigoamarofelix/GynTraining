<?php

namespace App\Http\Requests\Api\V1;

use App\Models\MuscleGroup;
use Illuminate\Foundation\Http\FormRequest;

class StoreMuscleGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', MuscleGroup::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
