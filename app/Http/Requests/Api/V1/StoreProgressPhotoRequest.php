<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProgressPhotoCategory;
use App\Enums\ProgressPhotoVisibility;
use App\Http\Requests\Concerns\EnsuresOwnStudentId;
use App\Models\ProgressPhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreProgressPhotoRequest extends FormRequest
{
    use EnsuresOwnStudentId;

    public function authorize(): bool
    {
        return $this->user()?->can('create', ProgressPhoto::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'category' => ['required', 'string', Rule::in(array_column(ProgressPhotoCategory::cases(), 'value'))],
            'photo' => ['required', 'image', 'max:5120'],
            'taken_at' => ['required', 'date'],
            'visibility' => ['nullable', 'string', Rule::in(array_column(ProgressPhotoVisibility::cases(), 'value'))],
            'notes' => ['nullable', 'string'],
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
