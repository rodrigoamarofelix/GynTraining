<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppNotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $data = is_array($this->data) ? $this->data : json_decode($this->data, true) ?? [];

        return [
            'id' => $this->id,
            'type' => $data['type'] ?? null,
            'title' => $data['title'] ?? null,
            'message' => $data['message'] ?? null,
            'data' => $data['data'] ?? [],
            'read_at' => $this->read_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
