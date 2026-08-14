<?php

namespace App\Notifications;

use App\Enums\NotificationType;

class PendingStudentRegistrationNotification extends BaseAppNotification
{
    public function __construct(
        private readonly string $studentName,
        private readonly string $studentEmail,
        private readonly string $gymName,
        private readonly int $studentId,
        private readonly int $gymId,
    ) {}

    public function notificationType(): NotificationType
    {
        return NotificationType::PendingStudentRegistration;
    }

    public function title(): string
    {
        return 'Novo cadastro pendente';
    }

    public function message(): string
    {
        return "{$this->studentName} ({$this->studentEmail}) solicitou cadastro na {$this->gymName}.";
    }

    public function payload(): array
    {
        return [
            'student_id' => $this->studentId,
            'student_name' => $this->studentName,
            'student_email' => $this->studentEmail,
            'gym_id' => $this->gymId,
            'gym_name' => $this->gymName,
            'action_url' => '/admin/alunos?status=pending',
        ];
    }
}
