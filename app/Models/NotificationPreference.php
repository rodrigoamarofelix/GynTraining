<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends BaseModel
{
    protected $fillable = [
        'user_id',
        'database_enabled',
        'mail_enabled',
        'push_enabled',
        'workout_reminders',
        'workout_updates',
        'inactivity_alerts',
        'reminder_time',
    ];

    protected function casts(): array
    {
        return [
            'database_enabled' => 'boolean',
            'mail_enabled' => 'boolean',
            'push_enabled' => 'boolean',
            'workout_reminders' => 'boolean',
            'workout_updates' => 'boolean',
            'inactivity_alerts' => 'boolean',
            'reminder_time' => 'datetime:H:i',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
