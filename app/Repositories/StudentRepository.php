<?php

namespace App\Repositories;

use App\Enums\ProfileStatus;
use App\Models\Student;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class StudentRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';

        $query = Student::query()
            ->with([
                'user' => fn ($userQuery) => $this->applyTrashedScope($userQuery, $scope),
                'gym',
                'trainer.user',
            ]);

        $this->applyScope($query, $scope);

        return $query
            ->when($filters['managed_gym_ids'] ?? null, fn ($query, $ids) => $query->whereIn('gym_id', $ids))
            ->when(
                ! isset($filters['managed_gym_ids']) && isset($filters['gym_id']),
                fn ($query) => $query->where('gym_id', $filters['gym_id']),
            )
            ->when($filters['trainer_id'] ?? null, fn ($query, $trainerId) => $query->where('trainer_id', $trainerId))
            ->when($filters['trainer_scope_id'] ?? null, function ($query, $trainerId) {
                $query->where(function ($inner) use ($trainerId) {
                    $inner->where('trainer_id', $trainerId)
                        ->orWhereHas('workoutPlans', fn ($planQuery) => $planQuery->where('trainer_id', $trainerId));
                });
            })
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

    public function findById(int $id, bool $withTrashed = false): ?Student
    {
        $query = Student::query()->with([
            'user' => fn ($userQuery) => $withTrashed ? $userQuery->withTrashed() : $userQuery,
            'gym',
            'trainer.user',
        ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    public function create(array $data): Student
    {
        return Student::query()->create($data);
    }

    public function update(Student $student, array $data): Student
    {
        $student->update($data);

        return $student->fresh(['user', 'gym', 'trainer.user']);
    }

    public function delete(Student $student): void
    {
        $student->delete();
    }

    public function restore(Student $student): Student
    {
        $student->restore();

        return $student->fresh([
            'user' => fn ($userQuery) => $userQuery->withTrashed(),
            'gym',
            'trainer.user',
        ]);
    }

    private function applyScope($query, string $scope): void
    {
        match ($scope) {
            'pending' => $query->where('status', ProfileStatus::Pending),
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
