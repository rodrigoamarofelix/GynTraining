<?php

namespace App\Services\Gym;

use App\Enums\GymActivityAction;
use App\Enums\GymStatus;
use App\Models\Gym;
use App\Models\User;
use App\Repositories\GymActivityLogRepository;

class GymActivityLogger
{
    private const FIELD_LABELS = [
        'name' => 'Nome',
        'description' => 'Descrição',
        'address' => 'Endereço',
        'phone' => 'Telefone',
        'email' => 'E-mail',
        'logo' => 'Logo',
        'status' => 'Status',
    ];

    public function __construct(
        private readonly GymActivityLogRepository $activityLogRepository,
    ) {}

    public function log(
        Gym $gym,
        ?User $performer,
        GymActivityAction $action,
        array $changes = [],
        ?string $summary = null,
    ): void {
        $this->activityLogRepository->create([
            'gym_id' => $gym->id,
            'performed_by' => $performer?->id,
            'action' => $action->value,
            'changes' => $changes,
            'summary' => $summary ?? $this->buildSummary($action, $changes, $performer),
        ]);
    }

    public function snapshot(Gym $gym): array
    {
        return [
            'name' => $gym->name,
            'description' => $gym->description,
            'address' => $gym->address,
            'phone' => $gym->phone,
            'email' => $gym->email,
            'logo' => $gym->logo,
            'status' => $gym->status?->value,
        ];
    }

    public function diff(array $before, array $after, array $submitted = []): array
    {
        $changes = [];

        foreach (self::FIELD_LABELS as $field => $label) {
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

    private function buildSummary(GymActivityAction $action, array $changes, ?User $performer): string
    {
        $actor = $performer?->name ?? 'Sistema';

        return match ($action) {
            GymActivityAction::Created => "{$actor} cadastrou a academia.",
            GymActivityAction::Updated => $changes === []
                ? "{$actor} atualizou a academia."
                : "{$actor} alterou: ".$this->changedLabels($changes).'.',
            GymActivityAction::Deleted => "{$actor} excluiu a academia (deleção lógica).",
            GymActivityAction::Restored => "{$actor} reativou a academia.",
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

        if ($value instanceof GymStatus) {
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
                GymStatus::Active->value => 'Ativa',
                GymStatus::Inactive->value => 'Inativa',
                default => (string) $value,
            },
            default => (string) $value,
        };
    }
}
