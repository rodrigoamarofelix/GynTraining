<?php

namespace App\Repositories;

use App\Models\WorkoutSet;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkoutSetRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return WorkoutSet::query()
            ->with(['workoutExercise'])
            ->when($filters['workout_exercise_id'] ?? null, fn ($q, $id) => $q->where('workout_exercise_id', $id))
            ->orderBy('set_number')
            ->paginate($perPage);
    }

    public function findById(int $id): ?WorkoutSet
    {
        return WorkoutSet::query()->with(['workoutExercise.workoutDay.workoutPlan'])->find($id);
    }

    public function create(array $data): WorkoutSet
    {
        return WorkoutSet::query()->create($data);
    }

    public function update(WorkoutSet $set, array $data): WorkoutSet
    {
        $set->update($data);

        return $set->fresh(['workoutExercise']);
    }

    public function softDelete(WorkoutSet $set): void
    {
        $set->delete();
    }
}
