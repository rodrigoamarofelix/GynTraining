<?php

namespace App\Http\Requests\Api\V1;

use App\Models\ExerciseCategory;
use Illuminate\Foundation\Http\FormRequest;

class StoreExerciseCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', ExerciseCategory::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
