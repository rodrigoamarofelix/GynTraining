<?php

namespace App\Http\Requests\Api\V1;

use App\Models\BodyMeasurement;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBodyMeasurementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $measurement = $this->route('body_measurement');

        return $measurement instanceof BodyMeasurement
            && ($this->user()?->can('update', $measurement) ?? false);
    }

    public function rules(): array
    {
        return [
            'measured_at' => ['sometimes', 'date'],
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
}
