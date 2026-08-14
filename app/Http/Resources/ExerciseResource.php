<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExerciseResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'instructions' => $this->instructions,
            'exercise_category_id' => $this->exercise_category_id,
            'muscle_group_id' => $this->muscle_group_id,
            'gym_id' => $this->gym_id,
            'equipment' => $this->equipment,
            'difficulty' => $this->difficulty?->value,
            'video_url' => $this->video_url,
            'image_url' => $this->image_url,
            'status' => $this->status?->value,
            'category' => new ExerciseCategoryResource($this->whenLoaded('category')),
            'muscle_group' => new MuscleGroupResource($this->whenLoaded('muscleGroup')),
            'gym' => new GymResource($this->whenLoaded('gym')),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
