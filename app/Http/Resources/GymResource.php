<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GymResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'logo' => $this->logo,
            'status' => $this->status?->value,
            'deleted_at' => $this->deleted_at?->toISOString(),
            'active_students_count' => $this->whenCounted('active_students_count'),
            'active_trainers_count' => $this->whenCounted('active_trainers_count'),
            'exercises_count' => $this->whenCounted('exercises_count'),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
