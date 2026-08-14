<?php

namespace App\Notifications;

use App\Enums\NotificationType;

class WorkoutPlanUpdatedNotification extends BaseAppNotification
{
    public function __construct(
        private readonly string $planName,
        private readonly ?string $trainerName = null,
        private readonly ?int $workoutPlanId = null,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::WorkoutPlanUpdated;
    }

    public function title(): string
    {
        return 'Ficha atualizada';
    }

    public function message(): string
    {
        $trainer = $this->trainerName ? "Seu professor {$this->trainerName}" : 'Seu professor';

        return "{$trainer} atualizou sua ficha \"{$this->planName}\".";
    }

    public function payload(): array
    {
        return [
            'workout_plan_id' => $this->workoutPlanId,
            'workout_plan_name' => $this->planName,
            'trainer_name' => $this->trainerName,
        ];
    }
}
