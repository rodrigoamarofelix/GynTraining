<?php

namespace App\Services\Muscle;

use App\Enums\MuscleGroupActivityAction;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Repositories\MuscleGroupActivityLogRepository;

class MuscleGroupActivityLogger
{
    private const FIELD_LABELS = [
        'name' => 'Nome',
        'description' => 'Descrição',
    ];

    public function __construct(
        private readonly MuscleGroupActivityLogRepository $activityLogRepository,
    ) {}

    public function log(
        MuscleGroup $muscleGroup,
        ?User $performer,
        MuscleGroupActivityAction $action,
        array $changes = [],
        ?string $summary = null,
    ): void {
        $this->activityLogRepository->create([
            'muscle_group_id' => $muscleGroup->id,
            'performed_by' => $performer?->id,
            'action' => $action->value,
            'changes' => $changes,
            'summary' => $summary ?? $this->buildSummary($action, $changes, $performer),
        ]);
    }

    public function snapshot(MuscleGroup $muscleGroup): array
    {
        return [
            'name' => $muscleGroup->name,
            'description' => $muscleGroup->description,
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

    private function buildSummary(MuscleGroupActivityAction $action, array $changes, ?User $performer): string
    {
        $actor = $performer?->name ?? 'Sistema';

        return match ($action) {
            MuscleGroupActivityAction::Created => "{$actor} cadastrou o grupo muscular.",
            MuscleGroupActivityAction::Updated => $changes === []
                ? "{$actor} atualizou o grupo muscular."
                : "{$actor} alterou: ".$this->changedLabels($changes).'.',
            MuscleGroupActivityAction::Deleted => "{$actor} excluiu o grupo muscular (deleção lógica).",
            MuscleGroupActivityAction::Restored => "{$actor} reativou o grupo muscular.",
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

        return (string) $value;
    }

    private function formatValue(string $field, mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
