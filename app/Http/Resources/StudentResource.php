<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'gym_id' => $this->gym_id,
            'trainer_id' => $this->trainer_id,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'notes' => $this->notes,
            'status' => $this->status?->value,
            'user' => new UserResource($this->whenLoaded('user')),
            'gym' => new GymResource($this->whenLoaded('gym')),
            'trainer' => new TrainerResource($this->whenLoaded('trainer')),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
