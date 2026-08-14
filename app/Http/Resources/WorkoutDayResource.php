<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutDayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_plan_id' => $this->workout_plan_id,
            'name' => $this->name,
            'description' => $this->description,
            'order' => $this->order,
            'exercises' => WorkoutExerciseResource::collection($this->whenLoaded('exercises')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
