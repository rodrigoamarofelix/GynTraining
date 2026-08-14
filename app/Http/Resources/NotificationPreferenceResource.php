<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationPreferenceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'database_enabled' => $this->database_enabled,
            'mail_enabled' => $this->mail_enabled,
            'push_enabled' => $this->push_enabled,
            'workout_reminders' => $this->workout_reminders,
            'workout_updates' => $this->workout_updates,
            'inactivity_alerts' => $this->inactivity_alerts,
            'reminder_time' => $this->reminder_time
                ? (is_string($this->reminder_time) ? $this->reminder_time : $this->reminder_time->format('H:i'))
                : '08:00',
        ];
    }
}
