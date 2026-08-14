<?php

namespace App\Services\Trainer;

use App\Enums\ProfileStatus;
use App\Enums\TrainerActivityAction;
use App\Models\Gym;
use App\Models\Trainer;
use App\Models\User;
use App\Repositories\TrainerActivityLogRepository;

class TrainerActivityLogger
{
    private const FIELD_LABELS = [
        'name' => 'Nome',
        'email' => 'E-mail',
        'phone' => 'Telefone',
        'gym_id' => 'Academia',
        'status' => 'Status',
        'specialty' => 'Especialidade',
        'bio' => 'Bio',
        'password' => 'Senha',
    ];

    public function __construct(
        private readonly TrainerActivityLogRepository $activityLogRepository,
    ) {}

    public function log(
        Trainer $trainer,
        ?User $performer,
        TrainerActivityAction $action,
        array $changes = [],
        ?string $summary = null,
    ): void {
        $this->activityLogRepository->create([
            'trainer_id' => $trainer->id,
            'performed_by' => $performer?->id,
            'action' => $action->value,
            'changes' => $changes,
            'summary' => $summary ?? $this->buildSummary($action, $changes, $performer),
        ]);
    }

    public function snapshot(Trainer $trainer): array
    {
        $trainer->loadMissing(['user', 'gym']);

        return [
            'name' => $trainer->user?->name,
            'email' => $trainer->user?->email,
            'phone' => $trainer->user?->phone,
            'gym_id' => $trainer->gym_id,
            'status' => $trainer->status?->value,
            'specialty' => $trainer->specialty,
            'bio' => $trainer->bio,
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

    private function buildSummary(TrainerActivityAction $action, array $changes, ?User $performer): string
    {
        $actor = $performer?->name ?? 'Sistema';

        return match ($action) {
            TrainerActivityAction::Created => "{$actor} cadastrou o professor.",
            TrainerActivityAction::Updated => $changes === []
                ? "{$actor} atualizou o professor."
                : "{$actor} alterou: ".$this->changedLabels($changes).'.',
            TrainerActivityAction::Deleted => "{$actor} excluiu o professor (deleção lógica).",
            TrainerActivityAction::Restored => "{$actor} reativou o professor.",
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
            default => (string) $value,
        };
    }
}
