<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'workout_session_id' => $this->workout_session_id,
            'workout_session_exercise_id' => $this->workout_session_exercise_id,
            'exercise_id' => $this->exercise_id,
            'student_id' => $this->student_id,
            'set_number' => $this->set_number,
            'repetitions' => $this->repetitions,
            'load' => $this->resource->getAttribute('load'),
            'rest_time' => $this->rest_time,
            'duration' => $this->duration,
            'notes' => $this->notes,
            'logged_at' => $this->logged_at?->toISOString(),
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
            'workout_session' => new WorkoutSessionResource($this->whenLoaded('workoutSession')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
