<?php

namespace App\Services\Workout;

use App\Models\WorkoutSet;
use App\Repositories\WorkoutSetRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkoutSetService
{
    public function __construct(
        private readonly WorkoutSetRepository $workoutSetRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->workoutSetRepository->paginate($filters, $perPage);
    }

    public function find(int $id): ?WorkoutSet
    {
        return $this->workoutSetRepository->findById($id);
    }

    public function create(array $data): WorkoutSet
    {
        return $this->workoutSetRepository->create($data)
            ->load(['workoutExercise']);
    }

    public function update(WorkoutSet $set, array $data): WorkoutSet
    {
        return $this->workoutSetRepository->update($set, $data);
    }

    public function delete(WorkoutSet $set): void
    {
        $this->workoutSetRepository->softDelete($set);
    }
}
