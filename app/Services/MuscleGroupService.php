<?php

namespace App\Services;

use App\Enums\MuscleGroupActivityAction;
use App\Models\MuscleGroup;
use App\Models\User;
use App\Repositories\MuscleGroupActivityLogRepository;
use App\Repositories\MuscleGroupRepository;
use App\Services\Muscle\MuscleGroupActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MuscleGroupService
{
    public function __construct(
        private readonly MuscleGroupRepository $muscleGroupRepository,
        private readonly MuscleGroupActivityLogger $activityLogger,
        private readonly MuscleGroupActivityLogRepository $activityLogRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->muscleGroupRepository->paginate($filters, $perPage);
    }

    public function find(int $id, bool $withTrashed = false): ?MuscleGroup
    {
        return $this->muscleGroupRepository->findById($id, $withTrashed);
    }

    public function activityLogs(int $muscleGroupId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activityLogRepository->paginateForMuscleGroup($muscleGroupId, $perPage);
    }

    public function create(array $data, ?User $performer = null): MuscleGroup
    {
        return DB::transaction(function () use ($data, $performer) {
            $data['slug'] = $this->makeUniqueSlug($data['name']);

            $muscleGroup = $this->muscleGroupRepository->create($data);

            $this->activityLogger->log(
                $muscleGroup,
                $performer,
                MuscleGroupActivityAction::Created,
                $this->activityLogger->diff([], $this->activityLogger->snapshot($muscleGroup), $data),
            );

            return $muscleGroup->fresh();
        });
    }

    public function update(MuscleGroup $muscleGroup, array $data, ?User $performer = null): MuscleGroup
    {
        return DB::transaction(function () use ($muscleGroup, $data, $performer) {
            $before = $this->activityLogger->snapshot($muscleGroup);

            if (isset($data['name']) && $data['name'] !== $muscleGroup->name) {
                $data['slug'] = $this->makeUniqueSlug($data['name'], $muscleGroup->id);
            }

            $muscleGroup = $this->muscleGroupRepository->update($muscleGroup, $data);
            $after = $this->activityLogger->snapshot($muscleGroup->fresh());
            $changes = $this->activityLogger->diff($before, $after, $data);

            if ($changes !== []) {
                $this->activityLogger->log($muscleGroup, $performer, MuscleGroupActivityAction::Updated, $changes);
            }

            return $muscleGroup;
        });
    }

    public function delete(MuscleGroup $muscleGroup, ?User $performer = null): void
    {
        DB::transaction(function () use ($muscleGroup, $performer) {
            $this->activityLogger->log($muscleGroup, $performer, MuscleGroupActivityAction::Deleted);

            $this->muscleGroupRepository->delete($muscleGroup);
        });
    }

    public function restore(int $muscleGroupId, User $performer): MuscleGroup
    {
        return DB::transaction(function () use ($muscleGroupId, $performer) {
            $muscleGroup = $this->muscleGroupRepository->findById($muscleGroupId, true);

            if (! $muscleGroup || ! $muscleGroup->trashed()) {
                abort(404, 'Grupo muscular não encontrado ou não está inativo.');
            }

            $muscleGroup = $this->muscleGroupRepository->restore($muscleGroup);

            $this->activityLogger->log($muscleGroup, $performer, MuscleGroupActivityAction::Restored);

            return $muscleGroup->fresh();
        });
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            MuscleGroup::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
