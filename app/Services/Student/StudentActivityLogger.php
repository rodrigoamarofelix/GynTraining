<?php

namespace App\Services\Student;

use App\Enums\ProfileStatus;
use App\Enums\StudentActivityAction;
use App\Models\Gym;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Repositories\StudentActivityLogRepository;

class StudentActivityLogger
{
    private const FIELD_LABELS = [
        'name' => 'Nome',
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'gym_id' => 'Academia',
        'trainer_id' => 'Professor',
        'status' => 'Status',
        'birth_date' => 'Data de nascimento',
        'notes' => 'Observações',
        'password' => 'Senha',
    ];

    public function __construct(
        private readonly StudentActivityLogRepository $activityLogRepository,
    ) {}

    public function log(
        Student $student,
        ?User $performer,
        StudentActivityAction $action,
        array $changes = [],
        ?string $summary = null,
    ): void {
        $this->activityLogRepository->create([
            'student_id' => $student->id,
            'performed_by' => $performer?->id,
            'action' => $action->value,
            'changes' => $changes,
            'summary' => $summary ?? $this->buildSummary($action, $changes, $performer),
        ]);
    }

    public function snapshot(Student $student): array
    {
        $student->loadMissing(['user', 'gym', 'trainer.user']);

        return [
            'name' => $student->user?->name,
            'email' => $student->user?->email,
            'phone' => $student->user?->phone,
            'gym_id' => $student->gym_id,
            'trainer_id' => $student->trainer_id,
            'status' => $student->status?->value,
            'birth_date' => $student->birth_date?->format('Y-m-d'),
            'notes' => $student->notes,
        ];
    }

    public function diff(array $before, array $after, array $submitted = []): array
    {
        $changes = [];

        foreach (self::FIELD_LABELS as $field => $label) {
            if ($field === 'password') {
                if (array_key_exists('password', $submitted) && filled($submitted['password'])) {
                    $changes[] = [
                        'field' => $field,
                        'label' => $label,
                        'old' => '********',
                        'new' => '********',
                    ];
                }

                continue;
            }

            $oldValue = $before[$field] ?? null;
            $newValue = $after[$field] ?? null;

            if ($this->normalizeValue($oldValue) === $this->normalizeValue($newValue)) {
                continue;
            }

            $changes[] = [
                'field' => $field,
                'label' => $label,
                'old' => $this->formatValue($field, $oldValue),
                'new' => $this->formatValue($field, $newValue),
            ];
        }

        return $changes;
    }

    private function buildSummary(StudentActivityAction $action, array $changes, ?User $performer): string
    {
        $actor = $performer?->name ?? 'Sistema';

        return match ($action) {
            StudentActivityAction::Created => "{$actor} cadastrou o aluno.",
            StudentActivityAction::Registered => 'Aluno realizou cadastro público aguardando aprovação.',
            StudentActivityAction::Updated => $changes === []
                ? "{$actor} atualizou o aluno."
                : "{$actor} alterou: ".$this->changedLabels($changes).'.',
            StudentActivityAction::Approved => "{$actor} aprovou o cadastro do aluno.",
            StudentActivityAction::Deleted => "{$actor} excluiu o aluno (deleção lógica).",
            StudentActivityAction::Restored => "{$actor} reativou o aluno.",
        };
    }

    private function changedLabels(array $changes): string
    {
        return collect($changes)
            ->pluck('label')
            ->filter()
            ->implode(', ');
    }

    private function normalizeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof ProfileStatus) {
            return $value->value;
        }

        return (string) $value;
    }

    private function formatValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return match ($field) {
            'status' => match ((string) $value) {
                ProfileStatus::Active->value => 'Ativo',
                ProfileStatus::Pending->value => 'Pendente',
                ProfileStatus::Inactive->value => 'Inativo',
                default => (string) $value,
            },
            'gym_id' => Gym::query()->find($value)?->name ?? (string) $value,
            'trainer_id' => Trainer::query()->with('user')->find($value)?->user?->name ?? 'Sem professor',
            default => (string) $value,
        };
    }
}
