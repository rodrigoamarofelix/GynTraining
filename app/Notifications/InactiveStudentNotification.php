<?php

namespace App\Notifications;

use App\Enums\NotificationType;

class InactiveStudentNotification extends BaseAppNotification
{
    public function __construct(
        private readonly int $daysSinceLastWorkout,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::InactiveStudent;
    }

    public function title(): string
    {
        return 'Sentimos sua falta';
    }

    public function message(): string
    {
        return "Você está há {$this->daysSinceLastWorkout} dias sem treinar. Que tal retomar hoje?";
    }

    public function payload(): array
    {
        return [
            'days_since_last_workout' => $this->daysSinceLastWorkout,
        ];
    }
}
