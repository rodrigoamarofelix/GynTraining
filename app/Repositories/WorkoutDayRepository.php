<?php

namespace App\Repositories;

use App\Models\WorkoutDay;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkoutDayRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return WorkoutDay::query()
            ->with(['workoutPlan', 'exercises.exercise', 'exercises.sets'])
            ->when($filters['workout_plan_id'] ?? null, fn ($q, $id) => $q->where('workout_plan_id', $id))
            ->orderBy('order')
            ->paginate($perPage);
    }

    public function findById(int $id): ?WorkoutDay
    {
        return WorkoutDay::query()
            ->with(['workoutPlan', 'exercises.exercise', 'exercises.sets'])
            ->find($id);
    }

    public function create(array $data): WorkoutDay
    {
        return WorkoutDay::query()->create($data);
    }

    public function update(WorkoutDay $day, array $data): WorkoutDay
    {
        $day->update($data);

        return $day->fresh(['workoutPlan', 'exercises.exercise', 'exercises.sets']);
    }

    public function softDelete(WorkoutDay $day): void
    {
        $day->delete();
    }
}
