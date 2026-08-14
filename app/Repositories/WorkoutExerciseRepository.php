<?php

namespace App\Repositories;

use App\Models\WorkoutExercise;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkoutExerciseRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return WorkoutExercise::query()
            ->with(['workoutDay', 'exercise', 'sets'])
            ->when($filters['workout_day_id'] ?? null, fn ($q, $id) => $q->where('workout_day_id', $id))
            ->orderBy('order')
            ->paginate($perPage);
    }

    public function findById(int $id): ?WorkoutExercise
    {
        return WorkoutExercise::query()->with(['workoutDay.workoutPlan', 'exercise', 'sets'])->find($id);
    }

    public function create(array $data): WorkoutExercise
    {
        return WorkoutExercise::query()->create($data);
    }

    public function update(WorkoutExercise $workoutExercise, array $data): WorkoutExercise
    {
        $workoutExercise->update($data);

        return $workoutExercise->fresh(['workoutDay', 'exercise', 'sets']);
    }

    public function softDelete(WorkoutExercise $workoutExercise): void
    {
        $workoutExercise->delete();
    }
}
