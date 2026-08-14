<?php

namespace App\Notifications;

use App\Enums\NotificationType;

class WorkoutAvailableNotification extends BaseAppNotification
{
    public function __construct(
        private readonly string $workoutDayName,
        private readonly ?int $workoutPlanId = null,
        private readonly ?int $workoutDayId = null,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::WorkoutAvailable;
    }

    public function title(): string
    {
        return 'Treino disponível';
    }

    public function message(): string
    {
        return "Seu treino de hoje está disponível: {$this->workoutDayName}.";
    }

    public function payload(): array
    {
        return [
            'workout_plan_id' => $this->workoutPlanId,
            'workout_day_id' => $this->workoutDayId,
            'workout_day_name' => $this->workoutDayName,
        ];
    }
}
