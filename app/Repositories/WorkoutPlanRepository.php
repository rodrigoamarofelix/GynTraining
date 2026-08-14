<?php

namespace App\Repositories;

use App\Models\WorkoutPlan;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkoutPlanRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $scope = $filters['scope'] ?? 'active';

        $query = WorkoutPlan::query()
            ->with(['student.user', 'trainer.user', 'days']);

        $this->applyScope($query, $scope);

        return $query
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->when($filters['trainer_id'] ?? null, fn ($q, $id) => $q->where('trainer_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where('name', 'ilike', "%{$search}%"))
            ->latest()
            ->paginate($perPage);
    }

    public function findById(int $id, bool $withTrashed = false): ?WorkoutPlan
    {
        $query = WorkoutPlan::query()
            ->with([
                'student.user',
                'trainer.user',
                'days' => fn ($dayQuery) => $this->withTrashedScope($dayQuery, $withTrashed)
                    ->with([
                        'exercises' => fn ($exerciseQuery) => $this->withTrashedScope($exerciseQuery, $withTrashed)
                            ->with([
                                'exercise',
                                'sets' => fn ($setQuery) => $this->withTrashedScope($setQuery, $withTrashed),
                            ]),
                    ]),
            ]);

        if ($withTrashed) {
            $query->withTrashed();
        }

        return $query->find($id);
    }

    private function withTrashedScope($query, bool $withTrashed)
    {
        return $withTrashed ? $query->withTrashed() : $query;
    }

    public function create(array $data): WorkoutPlan
    {
        return WorkoutPlan::query()->create($data);
    }

    public function update(WorkoutPlan $plan, array $data): WorkoutPlan
    {
        $plan->update($data);

        return $plan->fresh(['student.user', 'trainer.user', 'days.exercises.exercise', 'days.exercises.sets']);
    }

    public function softDelete(WorkoutPlan $plan): void
    {
        $plan->delete();
    }

    public function restore(WorkoutPlan $plan): WorkoutPlan
    {
        $plan->restore();

        return $plan->fresh(['student.user', 'trainer.user', 'days.exercises.exercise', 'days.exercises.sets']);
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
