<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GoalResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'exercise_id' => $this->exercise_id,
            'name' => $this->name,
            'description' => $this->description,
            'target' => (float) $this->target,
            'current_value' => (float) $this->current_value,
            'unit' => $this->unit,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'target_date' => $this->target_date?->format('Y-m-d'),
            'status' => $this->status?->value,
            'progress_percentage' => $this->progressPercentage(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'exercise' => new ExerciseResource($this->whenLoaded('exercise')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
