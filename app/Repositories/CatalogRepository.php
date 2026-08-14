<?php

namespace App\Repositories;

use App\Models\ExerciseCategory;
use App\Models\MuscleGroup;
use Illuminate\Database\Eloquent\Collection;

class CatalogRepository
{
    public function allMuscleGroups(): Collection
    {
        return MuscleGroup::query()->orderBy('name')->get();
    }

    public function findMuscleGroup(int $id): ?MuscleGroup
    {
        return MuscleGroup::query()->find($id);
    }

    public function createMuscleGroup(array $data): MuscleGroup
    {
        return MuscleGroup::query()->create($data);
    }

    public function updateMuscleGroup(MuscleGroup $muscleGroup, array $data): MuscleGroup
    {
        $muscleGroup->update($data);

        return $muscleGroup->fresh();
    }

    public function deleteMuscleGroup(MuscleGroup $muscleGroup): void
    {
        $muscleGroup->delete();
    }

    public function allExerciseCategories(): Collection
    {
        return ExerciseCategory::query()->orderBy('name')->get();
    }

    public function findExerciseCategory(int $id): ?ExerciseCategory
    {
        return ExerciseCategory::query()->find($id);
    }

    public function createExerciseCategory(array $data): ExerciseCategory
    {
        return ExerciseCategory::query()->create($data);
    }

    public function updateExerciseCategory(ExerciseCategory $category, array $data): ExerciseCategory
    {
        $category->update($data);

        return $category->fresh();
    }

    public function deleteExerciseCategory(ExerciseCategory $category): void
    {
        $category->delete();
    }
}
