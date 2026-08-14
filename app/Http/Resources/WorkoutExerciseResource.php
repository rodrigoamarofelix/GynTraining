<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_day_id' => $this->workout_day_id,
            'exercise_id' => $this->exercise_id,
            'order' => $this->order,
            'notes' => $this->notes,
            'execution_time' => $this->execution_time,
            'rest_time' => $this->rest_time,
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
            'sets' => WorkoutSetResource::collection($this->whenLoaded('sets')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
