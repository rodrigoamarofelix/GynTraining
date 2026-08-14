<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GymMemberResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->user?->name,
            'email' => $this->user?->email,
            'status' => $this->status?->value,
            'specialty' => $this->specialty ?? null,
            'deleted_at' => $this->deleted_at?->toISOString(),
        ];
    }
}
