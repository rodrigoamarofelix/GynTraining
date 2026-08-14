<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BodyMeasurementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'student_id' => $this->student_id,
            'measured_at' => $this->measured_at?->format('Y-m-d'),
            'weight' => $this->weight !== null ? (float) $this->weight : null,
            'height' => $this->height !== null ? (float) $this->height : null,
            'bmi' => $this->bmi !== null ? (float) $this->bmi : null,
            'arm' => $this->arm !== null ? (float) $this->arm : null,
            'forearm' => $this->forearm !== null ? (float) $this->forearm : null,
            'chest' => $this->chest !== null ? (float) $this->chest : null,
            'waist' => $this->waist !== null ? (float) $this->waist : null,
            'abdomen' => $this->abdomen !== null ? (float) $this->abdomen : null,
            'hip' => $this->hip !== null ? (float) $this->hip : null,
            'thigh' => $this->thigh !== null ? (float) $this->thigh : null,
            'calf' => $this->calf !== null ? (float) $this->calf : null,
            'body_fat_percentage' => $this->body_fat_percentage !== null ? (float) $this->body_fat_percentage : null,
            'notes' => $this->notes,
            'student' => new StudentResource($this->whenLoaded('student')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
