<?php

namespace App\Repositories;

use App\Models\ExerciseCategoryActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExerciseCategoryActivityLogRepository
{
    public function paginateForCategory(int $categoryId, int $perPage = 20): LengthAwarePaginator
    {
        return ExerciseCategoryActivityLog::query()
            ->with('performer')
            ->where('exercise_category_id', $categoryId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): ExerciseCategoryActivityLog
    {
        return ExerciseCategoryActivityLog::query()->create($data);
    }
}
