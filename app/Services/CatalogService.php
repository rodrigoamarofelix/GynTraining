<?php

namespace App\Services;

use App\Models\ExerciseCategory;
use App\Models\MuscleGroup;
use App\Repositories\CatalogRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class CatalogService
{
    public function __construct(
        private readonly CatalogRepository $catalogRepository,
    ) {}

    public function listMuscleGroups(): Collection
    {
        return $this->catalogRepository->allMuscleGroups();
    }

    public function createMuscleGroup(array $data): MuscleGroup
    {
        $data['slug'] = $this->makeUniqueMuscleGroupSlug($data['name']);

        return $this->catalogRepository->createMuscleGroup($data);
    }

    public function updateMuscleGroup(MuscleGroup $muscleGroup, array $data): MuscleGroup
    {
        if (isset($data['name']) && $data['name'] !== $muscleGroup->name) {
            $data['slug'] = $this->makeUniqueMuscleGroupSlug($data['name'], $muscleGroup->id);
        }

        return $this->catalogRepository->updateMuscleGroup($muscleGroup, $data);
    }

    public function deleteMuscleGroup(MuscleGroup $muscleGroup): void
    {
        $this->catalogRepository->deleteMuscleGroup($muscleGroup);
    }

    public function listExerciseCategories(): Collection
    {
        return $this->catalogRepository->allExerciseCategories();
    }

    public function createExerciseCategory(array $data): ExerciseCategory
    {
        $data['slug'] = $this->makeUniqueCategorySlug($data['name']);

        return $this->catalogRepository->createExerciseCategory($data);
    }

    public function updateExerciseCategory(ExerciseCategory $category, array $data): ExerciseCategory
    {
        if (isset($data['name']) && $data['name'] !== $category->name) {
            $data['slug'] = $this->makeUniqueCategorySlug($data['name'], $category->id);
        }

        return $this->catalogRepository->updateExerciseCategory($category, $data);
    }

    public function deleteExerciseCategory(ExerciseCategory $category): void
    {
        $this->catalogRepository->deleteExerciseCategory($category);
    }

    private function makeUniqueMuscleGroupSlug(string $name, ?int $ignoreId = null): string
    {
        return $this->makeUniqueSlug(MuscleGroup::class, $name, $ignoreId);
    }

    private function makeUniqueCategorySlug(string $name, ?int $ignoreId = null): string
    {
        return $this->makeUniqueSlug(ExerciseCategory::class, $name, $ignoreId);
    }

    private function makeUniqueSlug(string $modelClass, string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            $modelClass::query()
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
