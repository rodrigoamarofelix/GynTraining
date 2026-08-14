<?php

namespace App\Repositories;

use App\Enums\ProfileStatus;
use App\Models\Trainer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TrainerRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';

        $query = Trainer::query()
            ->with([
                'user' => fn ($userQuery) => $this->applyTrashedScope($userQuery, $scope),
                'gym',
            ]);

        $this->applyScope($query, $scope);

        return $query
            ->when($filters['managed_gym_ids'] ?? null, fn ($query, $ids) => $query->whereIn('gym_id', $ids))
            ->when(
                ! isset($filters['managed_gym_ids']) && isset($filters['gym_id']),
                fn ($query) => $query->where('gym_id', $filters['gym_id']),
            )
            ->when($filters['search'] ?? null, function ($query, $search) use ($scope) {
                $query->whereHas('user', function ($userQuery) use ($search, $scope) {
                    $this->applyTrashedScope($userQuery, $scope);

                    $userQuery
                        ->where('name', 'ilike', "%{$search}%")
                        ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id, bool $withTrashed = false): ?Trainer
    {
        $query = Trainer::query()->with([
            'user' => fn ($userQuery) => $withTrashed ? $userQuery->withTrashed() : $userQuery,
            'gym',
        ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): Trainer
    {
        return Trainer::query()->create($data);
    }

    public function update(Trainer $trainer, array $data): Trainer
    {
        $trainer->update($data);

        return $trainer->fresh(['user', 'gym']);
    }

    public function delete(Trainer $trainer): void
    {
        $trainer->delete();
    }

    public function restore(Trainer $trainer): Trainer
    {
        $trainer->restore();

        return $trainer->fresh([
            'user' => fn ($userQuery) => $userQuery->withTrashed(),
            'gym',
        ]);
    }

    private function applyScope($query, string $scope): void
    {
        match ($scope) {
            'inactive' => $query->withTrashed()->where(function ($inner) {
                $inner->where('status', ProfileStatus::Inactive)
                    ->orWhereNotNull('deleted_at');
            }),
            'all' => $query,
            default => $query->where('status', ProfileStatus::Active),
        };
    }

    private function applyTrashedScope($query, string $scope)
    {
        if ($scope === 'inactive') {
            $query->withTrashed();
        }

        return $query;
    }
}
