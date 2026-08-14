<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WorkoutSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'workout_plan_id' => $this->workout_plan_id,
            'workout_day_id' => $this->workout_day_id,
            'started_at' => $this->started_at?->toISOString(),
            'finished_at' => $this->finished_at?->toISOString(),
            'duration_seconds' => $this->duration_seconds,
            'status' => $this->status?->value,
            'notes' => $this->notes,
            'student' => new StudentResource($this->whenLoaded('student')),
            'workout_plan' => new WorkoutPlanResource($this->whenLoaded('workoutPlan')),
            'workout_day' => new WorkoutDayResource($this->whenLoaded('workoutDay')),
            'session_exercises' => WorkoutSessionExerciseResource::collection($this->whenLoaded('sessionExercises')),
            'exercise_logs' => ExerciseLogResource::collection($this->whenLoaded('exerciseLogs')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
