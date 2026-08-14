<?php

namespace App\Services;

use App\Enums\ExerciseCategoryActivityAction;
use App\Models\ExerciseCategory;
use App\Models\User;
use App\Repositories\ExerciseCategoryActivityLogRepository;
use App\Repositories\ExerciseCategoryRepository;
use App\Services\Exercise\ExerciseCategoryActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ExerciseCategoryService
{
    public function __construct(
        private readonly ExerciseCategoryRepository $exerciseCategoryRepository,
        private readonly ExerciseCategoryActivityLogger $activityLogger,
        private readonly ExerciseCategoryActivityLogRepository $activityLogRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->exerciseCategoryRepository->paginate($filters, $perPage);
    }

    public function find(int $id, bool $withTrashed = false): ?ExerciseCategory
    {
        return $this->exerciseCategoryRepository->findById($id, $withTrashed);
    }

    public function activityLogs(int $categoryId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activityLogRepository->paginateForCategory($categoryId, $perPage);
    }

    public function create(array $data, ?User $performer = null): ExerciseCategory
    {
        return DB::transaction(function () use ($data, $performer) {
            $data['slug'] = $this->makeUniqueSlug($data['name']);

            $category = $this->exerciseCategoryRepository->create($data);

            $this->activityLogger->log(
                $category,
                $performer,
                ExerciseCategoryActivityAction::Created,
                $this->activityLogger->diff([], $this->activityLogger->snapshot($category), $data),
            );

            return $category->fresh();
        });
    }

    public function update(ExerciseCategory $category, array $data, ?User $performer = null): ExerciseCategory
    {
        return DB::transaction(function () use ($category, $data, $performer) {
            $before = $this->activityLogger->snapshot($category);

            if (isset($data['name']) && $data['name'] !== $category->name) {
                $data['slug'] = $this->makeUniqueSlug($data['name'], $category->id);
            }

            $category = $this->exerciseCategoryRepository->update($category, $data);
            $after = $this->activityLogger->snapshot($category->fresh());
            $changes = $this->activityLogger->diff($before, $after, $data);

            if ($changes !== []) {
                $this->activityLogger->log($category, $performer, ExerciseCategoryActivityAction::Updated, $changes);
            }

            return $category;
        });
    }

    public function delete(ExerciseCategory $category, ?User $performer = null): void
    {
        DB::transaction(function () use ($category, $performer) {
            $this->activityLogger->log($category, $performer, ExerciseCategoryActivityAction::Deleted);

            $this->exerciseCategoryRepository->delete($category);
        });
    }

    public function restore(int $categoryId, User $performer): ExerciseCategory
    {
        return DB::transaction(function () use ($categoryId, $performer) {
            $category = $this->exerciseCategoryRepository->findById($categoryId, true);

            if (! $category || ! $category->trashed()) {
                abort(404, 'Categoria não encontrada ou não está inativa.');
            }

            $category = $this->exerciseCategoryRepository->restore($category);

            $this->activityLogger->log($category, $performer, ExerciseCategoryActivityAction::Restored);

            return $category->fresh();
        });
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            ExerciseCategory::query()
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
