<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProgressPhotoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'category' => $this->category?->value,
            'photo_url' => $this->photoUrl(),
            'taken_at' => $this->taken_at?->format('Y-m-d'),
            'visibility' => $this->visibility?->value,
            'notes' => $this->notes,
            'student' => new StudentResource($this->whenLoaded('student')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
