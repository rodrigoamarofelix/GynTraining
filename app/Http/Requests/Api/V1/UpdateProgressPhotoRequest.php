<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\ProgressPhotoCategory;
use App\Enums\ProgressPhotoVisibility;
use App\Models\ProgressPhoto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProgressPhotoRequest extends FormRequest
{
    public function authorize(): bool
    {
        $photo = $this->route('progress_photo');

        return $photo instanceof ProgressPhoto
            && ($this->user()?->can('update', $photo) ?? false);
    }

    public function rules(): array
    {
        return [
            'category' => ['sometimes', 'string', Rule::in(array_column(ProgressPhotoCategory::cases(), 'value'))],
            'photo' => ['sometimes', 'image', 'max:5120'],
            'taken_at' => ['sometimes', 'date'],
            'visibility' => ['nullable', 'string', Rule::in(array_column(ProgressPhotoVisibility::cases(), 'value'))],
            'notes' => ['nullable', 'string'],
        ];
    }
}
