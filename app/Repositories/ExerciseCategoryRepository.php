<?php

namespace App\Repositories;

use App\Models\ExerciseCategory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExerciseCategoryRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';

        $query = ExerciseCategory::query()
            ->withCount('exercises');

        $this->applyScope($query, $scope);

        return $query
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id, bool $withTrashed = false): ?ExerciseCategory
    {
        $query = ExerciseCategory::query()
            ->withCount('exercises');

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): ExerciseCategory
    {
        return ExerciseCategory::query()->create($data);
    }

    public function update(ExerciseCategory $category, array $data): ExerciseCategory
    {
        $category->update($data);

        return $category->fresh();
    }

    public function delete(ExerciseCategory $category): void
    {
        $category->delete();
    }

    public function restore(ExerciseCategory $category): ExerciseCategory
    {
        $category->restore();

        return $category->fresh();
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
