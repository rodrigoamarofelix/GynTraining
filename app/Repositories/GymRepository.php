<?php

namespace App\Repositories;

use App\Enums\GymStatus;
use App\Models\Gym;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GymRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';

        $query = Gym::query()
            ->withCount([
                'students as active_students_count' => fn ($studentQuery) => $studentQuery
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
                'trainers as active_trainers_count' => fn ($trainerQuery) => $trainerQuery
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
                'exercises as exercises_count',
            ]);

        $this->applyScope($query, $scope);

        return $query
            ->when($filters['managed_gym_ids'] ?? null, fn ($query, $ids) => $query->whereIn('id', $ids))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where('name', 'ilike', "%{$search}%"))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id, bool $withTrashed = false): ?Gym
    {
        $query = Gym::query()
            ->withCount([
                'students as active_students_count' => fn ($studentQuery) => $studentQuery
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
                'trainers as active_trainers_count' => fn ($trainerQuery) => $trainerQuery
                    ->where('status', 'active')
                    ->whereNull('deleted_at'),
                'exercises as exercises_count',
            ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): Gym
    {
        return Gym::query()->create($data);
    }

    public function update(Gym $gym, array $data): Gym
    {
        $gym->update($data);

        return $gym->fresh();
    }

    public function delete(Gym $gym): void
    {
        $gym->delete();
    }

    public function restore(Gym $gym): Gym
    {
        $gym->restore();

        return $gym->fresh();
    }

    private function applyScope($query, string $scope): void
    {
        match ($scope) {
            'inactive' => $query->withTrashed()->where(function ($inner) {
                $inner->where('status', GymStatus::Inactive)
                    ->orWhereNotNull('deleted_at');
            }),
            'all' => $query,
            default => $query->where('status', GymStatus::Active),
        };
    }
}
