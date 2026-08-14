<?php

namespace App\Services\Exercise;

use App\Enums\ExerciseCategoryActivityAction;
use App\Models\ExerciseCategory;
use App\Models\User;
use App\Repositories\ExerciseCategoryActivityLogRepository;

class ExerciseCategoryActivityLogger
{
    private const FIELD_LABELS = [
        'name' => 'Nome',
        'description' => 'Descrição',
    ];

    public function __construct(
        private readonly ExerciseCategoryActivityLogRepository $activityLogRepository,
    ) {}

    public function log(
        ExerciseCategory $category,
        ?User $performer,
        ExerciseCategoryActivityAction $action,
        array $changes = [],
        ?string $summary = null,
    ): void {
        $this->activityLogRepository->create([
            'exercise_category_id' => $category->id,
            'performed_by' => $performer?->id,
            'action' => $action->value,
            'changes' => $changes,
            'summary' => $summary ?? $this->buildSummary($action, $changes, $performer),
        ]);
    }

    public function snapshot(ExerciseCategory $category): array
    {
        return [
            'name' => $category->name,
            'description' => $category->description,
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
                'old' => $this->formatValue($oldValue),
                'new' => $this->formatValue($newValue),
            ];
        }

        return $changes;
    }

    private function buildSummary(ExerciseCategoryActivityAction $action, array $changes, ?User $performer): string
    {
        $actor = $performer?->name ?? 'Sistema';

        return match ($action) {
            ExerciseCategoryActivityAction::Created => "{$actor} cadastrou a categoria.",
            ExerciseCategoryActivityAction::Updated => $changes === []
                ? "{$actor} atualizou a categoria."
                : "{$actor} alterou: ".$this->changedLabels($changes).'.',
            ExerciseCategoryActivityAction::Deleted => "{$actor} excluiu a categoria (deleção lógica).",
            ExerciseCategoryActivityAction::Restored => "{$actor} reativou a categoria.",
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

    private function formatValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (string) $value;
    }
}
