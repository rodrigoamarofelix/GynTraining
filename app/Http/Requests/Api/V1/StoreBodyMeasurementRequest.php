<?php

namespace App\Http\Requests\Api\V1;

use App\Http\Requests\Concerns\EnsuresOwnStudentId;
use App\Models\BodyMeasurement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreBodyMeasurementRequest extends FormRequest
{
    use EnsuresOwnStudentId;

    public function authorize(): bool
    {
        return $this->user()?->can('create', BodyMeasurement::class) ?? false;
    }

    public function rules(): array
    {
        return [
            'student_id' => ['required', 'exists:students,id'],
            'measured_at' => ['required', 'date'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'height' => ['nullable', 'numeric', 'min:0'],
            'arm' => ['nullable', 'numeric', 'min:0'],
            'forearm' => ['nullable', 'numeric', 'min:0'],
            'chest' => ['nullable', 'numeric', 'min:0'],
            'waist' => ['nullable', 'numeric', 'min:0'],
            'abdomen' => ['nullable', 'numeric', 'min:0'],
            'hip' => ['nullable', 'numeric', 'min:0'],
            'thigh' => ['nullable', 'numeric', 'min:0'],
            'calf' => ['nullable', 'numeric', 'min:0'],
            'body_fat_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->mergeOwnStudentIdIfMissing();
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->ensureOwnStudentId($validator);

            $fields = ['weight', 'height', 'arm', 'forearm', 'chest', 'waist', 'abdomen', 'hip', 'thigh', 'calf', 'body_fat_percentage'];

            if (! collect($fields)->contains(fn (string $field) => $this->filled($field))) {
                $validator->errors()->add('weight', 'Informe ao menos uma medida ou o peso.');
            }
        });
    }
}
