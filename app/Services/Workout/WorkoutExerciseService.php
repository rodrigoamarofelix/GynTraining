<?php

namespace App\Services\Workout;

use App\Models\WorkoutExercise;
use App\Models\WorkoutSet;
use App\Repositories\WorkoutExerciseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorkoutExerciseService
{
    public function __construct(
        private readonly WorkoutExerciseRepository $workoutExerciseRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->workoutExerciseRepository->paginate($filters, $perPage);
    }

    public function find(int $id): ?WorkoutExercise
    {
        return $this->workoutExerciseRepository->findById($id);
    }

    public function create(array $data): WorkoutExercise
    {
        return DB::transaction(function () use ($data) {
            $sets = $data['sets'] ?? [];
            unset($data['sets']);

            $workoutExercise = $this->workoutExerciseRepository->create($data);

            foreach ($sets as $setData) {
                $workoutExercise->sets()->create($setData);
            }

            return $this->workoutExerciseRepository->findById($workoutExercise->id);
        });
    }

    public function update(WorkoutExercise $workoutExercise, array $data): WorkoutExercise
    {
        unset($data['sets']);

        return $this->workoutExerciseRepository->update($workoutExercise, $data);
    }

    public function delete(WorkoutExercise $workoutExercise): void
    {
        DB::transaction(function () use ($workoutExercise) {
            $workoutExercise->sets()->each(fn (WorkoutSet $set) => $set->delete());
            $this->workoutExerciseRepository->softDelete($workoutExercise);
        });
    }
}
