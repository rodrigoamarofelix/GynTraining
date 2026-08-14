<?php

namespace App\Notifications;

use App\Enums\NotificationType;

class WorkoutReminderNotification extends BaseAppNotification
{
    public function __construct(
        private readonly string $workoutDayName,
        private readonly ?int $workoutPlanId = null,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::WorkoutReminder;
    }

    public function title(): string
    {
        return 'Hora do treino';
    }

    public function message(): string
    {
        return "Hora do treino! Hoje é dia de {$this->workoutDayName}.";
    }

    public function payload(): array
    {
        return [
            'workout_plan_id' => $this->workoutPlanId,
            'workout_day_name' => $this->workoutDayName,
        ];
    }
}
