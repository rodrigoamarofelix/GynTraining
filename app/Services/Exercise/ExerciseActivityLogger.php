<?php

namespace App\Services\Exercise;

use App\Enums\ExerciseActivityAction;
use App\Enums\ExerciseDifficulty;
use App\Enums\ExerciseStatus;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\Gym;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Repositories\ExerciseActivityLogRepository;

class ExerciseActivityLogger
{
    private const FIELD_LABELS = [
        'name' => 'Nome',
        'description' => 'Descrição',
        'instructions' => 'Instruções',
        'muscle_group_id' => 'Grupo muscular',
        'exercise_category_id' => 'Categoria',
        'gym_id' => 'Academia',
        'equipment' => 'Equipamento',
        'difficulty' => 'Dificuldade',
        'status' => 'Status',
        'video_url' => 'URL do vídeo',
        'image_url' => 'URL da imagem',
    ];

    public function __construct(
        private readonly ExerciseActivityLogRepository $activityLogRepository,
    ) {}

    public function log(
        Exercise $exercise,
        ?User $performer,
        ExerciseActivityAction $action,
        array $changes = [],
        ?string $summary = null,
    ): void {
        $this->activityLogRepository->create([
            'exercise_id' => $exercise->id,
            'performed_by' => $performer?->id,
            'action' => $action->value,
            'changes' => $changes,
            'summary' => $summary ?? $this->buildSummary($action, $changes, $performer),
        ]);
    }

    public function snapshot(Exercise $exercise): array
    {
        $exercise->loadMissing(['category', 'muscleGroup', 'gym']);

        return [
            'name' => $exercise->name,
            'description' => $exercise->description,
            'instructions' => $exercise->instructions,
            'muscle_group_id' => $exercise->muscle_group_id,
            'exercise_category_id' => $exercise->exercise_category_id,
            'gym_id' => $exercise->gym_id,
            'equipment' => $exercise->equipment,
            'difficulty' => $exercise->difficulty?->value,
            'status' => $exercise->status?->value,
            'video_url' => $exercise->video_url,
            'image_url' => $exercise->image_url,
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

    private function buildSummary(ExerciseActivityAction $action, array $changes, ?User $performer): string
    {
        $actor = $performer?->name ?? 'Sistema';

        return match ($action) {
            ExerciseActivityAction::Created => "{$actor} cadastrou o exercício.",
            ExerciseActivityAction::Updated => $changes === []
                ? "{$actor} atualizou o exercício."
                : "{$actor} alterou: ".$this->changedLabels($changes).'.',
            ExerciseActivityAction::Deleted => "{$actor} excluiu o exercício (deleção lógica).",
            ExerciseActivityAction::Restored => "{$actor} reativou o exercício.",
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

        if ($value instanceof ExerciseStatus || $value instanceof ExerciseDifficulty) {
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
                ExerciseStatus::Active->value => 'Ativo',
                ExerciseStatus::Inactive->value => 'Inativo',
                default => (string) $value,
            },
            'difficulty' => match ((string) $value) {
                ExerciseDifficulty::Beginner->value => 'Iniciante',
                ExerciseDifficulty::Intermediate->value => 'Intermediário',
                ExerciseDifficulty::Advanced->value => 'Avançado',
                default => (string) $value,
            },
            'gym_id' => Gym::query()->find($value)?->name ?? (string) $value,
            'muscle_group_id' => MuscleGroup::query()->find($value)?->name ?? (string) $value,
            'exercise_category_id' => ExerciseCategory::query()->find($value)?->name ?? (string) $value,
            default => (string) $value,
        };
    }
}
