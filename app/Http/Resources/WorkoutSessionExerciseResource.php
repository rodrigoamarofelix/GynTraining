<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSessionExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_session_id' => $this->workout_session_id,
            'exercise_id' => $this->exercise_id,
            'workout_exercise_id' => $this->workout_exercise_id,
            'order' => $this->order,
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),
            'notes' => $this->notes,
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
            'logs' => ExerciseLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
