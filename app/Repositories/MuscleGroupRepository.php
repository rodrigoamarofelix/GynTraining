<?php

namespace App\Repositories;

use App\Models\MuscleGroup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MuscleGroupRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';

        $query = MuscleGroup::query()
            ->withCount('exercises');

        $this->applyScope($query, $scope);

        return $query
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id, bool $withTrashed = false): ?MuscleGroup
    {
        $query = MuscleGroup::query()
            ->withCount('exercises');

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): MuscleGroup
    {
        return MuscleGroup::query()->create($data);
    }

    public function update(MuscleGroup $muscleGroup, array $data): MuscleGroup
    {
        $muscleGroup->update($data);

        return $muscleGroup->fresh();
    }

    public function delete(MuscleGroup $muscleGroup): void
    {
        $muscleGroup->delete();
    }

    public function restore(MuscleGroup $muscleGroup): MuscleGroup
    {
        $muscleGroup->restore();

        return $muscleGroup->fresh();
    }

    private function applyScope($query, string $scope): void
    {
        match ($scope) {
            'inactive' => $query->onlyTrashed(),
            'all' => $query->withTrashed(),
            default => $query,
        };
    }
}
