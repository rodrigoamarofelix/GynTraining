<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkoutSetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('workout_set')) ?? false;
    }

    public function rules(): array
    {
        return [
            'set_number' => ['sometimes', 'integer', 'min:1'],
            'repetitions' => ['nullable', 'integer', 'min:0'],
            'load' => ['nullable', 'numeric', 'min:0'],
            'rest_time' => ['nullable', 'integer', 'min:0'],
            'duration' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
