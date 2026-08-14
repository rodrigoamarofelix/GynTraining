<?php

namespace App\Services;

use App\Enums\ExerciseActivityAction;
use App\Enums\ExerciseStatus;
use App\Models\Exercise;
use App\Models\User;
use App\Repositories\ExerciseActivityLogRepository;
use App\Repositories\ExerciseRepository;
use App\Services\Exercise\ExerciseActivityLogger;
use App\Support\ManagedGymScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ExerciseService
{
    public function __construct(
        private readonly ExerciseRepository $exerciseRepository,
        private readonly ExerciseActivityLogger $activityLogger,
        private readonly ExerciseActivityLogRepository $activityLogRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->exerciseRepository->paginate($filters, $perPage);
    }

    public function filtersForUser(User $user): array
    {
        $ids = ManagedGymScope::idsFor($user);

        if ($ids === null) {
            return [];
        }

        return ['managed_gym_ids' => $ids];
    }

    public function find(int $id, bool $withTrashed = false): ?Exercise
    {
        return $this->exerciseRepository->findById($id, $withTrashed);
    }

    public function activityLogs(int $exerciseId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activityLogRepository->paginateForExercise($exerciseId, $perPage);
    }

    public function create(array $data, ?User $performer = null): Exercise
    {
        return DB::transaction(function () use ($data, $performer) {
            $data['status'] = $data['status'] ?? ExerciseStatus::Active->value;

            $exercise = $this->exerciseRepository->create($data)->load(['category', 'muscleGroup', 'gym']);

            $this->activityLogger->log(
                $exercise,
                $performer,
                ExerciseActivityAction::Created,
                $this->activityLogger->diff([], $this->activityLogger->snapshot($exercise), $data),
            );

            return $exercise;
        });
    }

    public function update(Exercise $exercise, array $data, ?User $performer = null): Exercise
    {
        return DB::transaction(function () use ($exercise, $data, $performer) {
            $before = $this->activityLogger->snapshot($exercise);

            $exercise = $this->exerciseRepository->update($exercise, $data);

            $after = $this->activityLogger->snapshot($exercise);
            $changes = $this->activityLogger->diff($before, $after, $data);

            if ($changes !== []) {
                $this->activityLogger->log($exercise, $performer, ExerciseActivityAction::Updated, $changes);
            }

            return $exercise;
        });
    }

    public function delete(Exercise $exercise, ?User $performer = null): void
    {
        DB::transaction(function () use ($exercise, $performer) {
            $this->activityLogger->log($exercise, $performer, ExerciseActivityAction::Deleted);

            $this->exerciseRepository->delete($exercise);
        });
    }

    public function restore(int $exerciseId, User $performer): Exercise
    {
        return DB::transaction(function () use ($exerciseId, $performer) {
            $exercise = $this->exerciseRepository->findById($exerciseId, true);

            if (! $exercise || ! $exercise->trashed()) {
                abort(404, 'Exercício não encontrado ou não está inativo.');
            }

            $exercise = $this->exerciseRepository->restore($exercise);
            $exercise->update(['status' => ExerciseStatus::Active]);

            $exercise = $exercise->fresh(['category', 'muscleGroup', 'gym']);

            $this->activityLogger->log($exercise, $performer, ExerciseActivityAction::Restored);

            return $exercise;
        });
    }
}
