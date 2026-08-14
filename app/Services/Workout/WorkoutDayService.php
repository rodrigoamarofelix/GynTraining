<?php

namespace App\Services\Workout;

use App\Models\WorkoutDay;
use App\Models\WorkoutSet;
use App\Repositories\WorkoutDayRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorkoutDayService
{
    public function __construct(
        private readonly WorkoutDayRepository $workoutDayRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->workoutDayRepository->paginate($filters, $perPage);
    }

    public function find(int $id): ?WorkoutDay
    {
        return $this->workoutDayRepository->findById($id);
    }

    public function create(array $data): WorkoutDay
    {
        return $this->workoutDayRepository->create($data)
            ->load(['workoutPlan', 'exercises.exercise', 'exercises.sets']);
    }

    public function update(WorkoutDay $day, array $data): WorkoutDay
    {
        return $this->workoutDayRepository->update($day, $data);
    }

    public function delete(WorkoutDay $day): void
    {
        DB::transaction(function () use ($day) {
            $day->load(['exercises.sets']);

            foreach ($day->exercises as $exercise) {
                $exercise->sets->each(fn (WorkoutSet $set) => $set->delete());
                $exercise->delete();
            }

            $this->workoutDayRepository->softDelete($day);
        });
    }
}
