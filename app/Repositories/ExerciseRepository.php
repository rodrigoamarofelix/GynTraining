<?php

namespace App\Repositories;

use App\Enums\ExerciseStatus;
use App\Models\Exercise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExerciseRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';

        $query = Exercise::query()
            ->with(['category', 'muscleGroup', 'gym']);

        $this->applyScope($query, $scope);

        return $query
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->when($filters['muscle_group_id'] ?? null, fn ($query, $id) => $query->where('muscle_group_id', $id))
            ->when($filters['exercise_category_id'] ?? null, fn ($query, $id) => $query->where('exercise_category_id', $id))
            ->when($filters['managed_gym_ids'] ?? null, fn ($query, $ids) => $query->where(function ($inner) use ($ids) {
                $inner->whereNull('gym_id')->orWhereIn('gym_id', $ids);
            }))
            ->when(
                ! isset($filters['managed_gym_ids']) && isset($filters['gym_id']),
                fn ($query) => $query->where(function ($inner) use ($filters) {
                    $gymId = $filters['gym_id'];
                    $inner->whereNull('gym_id')->orWhere('gym_id', $gymId);
                }),
            )
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id, bool $withTrashed = false): ?Exercise
    {
        $query = Exercise::query()->with(['category', 'muscleGroup', 'gym']);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): Exercise
    {
        return Exercise::query()->create($data);
    }

    public function update(Exercise $exercise, array $data): Exercise
    {
        $exercise->update($data);

        return $exercise->fresh(['category', 'muscleGroup', 'gym']);
    }

    public function delete(Exercise $exercise): void
    {
        $exercise->delete();
    }

    public function restore(Exercise $exercise): Exercise
    {
        $exercise->restore();

        return $exercise->fresh(['category', 'muscleGroup', 'gym']);
    }

    private function applyScope($query, string $scope): void
    {
        match ($scope) {
            'inactive' => $query->withTrashed()->where(function ($inner) {
                $inner->where('status', ExerciseStatus::Inactive)
                    ->orWhereNotNull('deleted_at');
            }),
            'all' => $query,
            default => $query->where('status', ExerciseStatus::Active),
        };
    }
}
