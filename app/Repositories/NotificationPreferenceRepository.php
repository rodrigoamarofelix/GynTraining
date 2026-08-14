<?php

namespace App\Repositories;

use App\Models\NotificationPreference;
use App\Models\User;

class NotificationPreferenceRepository
{
    public function findForUser(int $userId): ?NotificationPreference
    {
        return NotificationPreference::query()->where('user_id', $userId)->first();
    }

    public function getOrCreateForUser(User $user): NotificationPreference
    {
        return NotificationPreference::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'database_enabled' => true,
                'mail_enabled' => true,
                'push_enabled' => false,
                'workout_reminders' => true,
                'workout_updates' => true,
                'inactivity_alerts' => true,
                'reminder_time' => '08:00:00',
            ],
        );
    }

    public function update(NotificationPreference $preference, array $data): NotificationPreference
    {
        $preference->update($data);

        return $preference->fresh();
    }
}
