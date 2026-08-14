<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSetResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_exercise_id' => $this->workout_exercise_id,
            'set_number' => $this->set_number,
            'repetitions' => $this->repetitions,
            'load' => $this->resource->getAttribute('load'),
            'rest_time' => $this->rest_time,
            'duration' => $this->duration,
            'notes' => $this->notes,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
