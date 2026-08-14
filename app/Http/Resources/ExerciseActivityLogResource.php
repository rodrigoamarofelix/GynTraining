<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseActivityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exercise_id' => $this->exercise_id,
            'action' => $this->action?->value,
            'summary' => $this->summary,
            'changes' => $this->changes ?? [],
            'performed_by' => $this->performed_by,
            'performer' => new UserResource($this->whenLoaded('performer')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
