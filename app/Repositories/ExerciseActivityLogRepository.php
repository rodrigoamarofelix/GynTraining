<?php

namespace App\Repositories;

use App\Models\ExerciseActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ExerciseActivityLogRepository
{
    public function paginateForExercise(int $exerciseId, int $perPage = 20): LengthAwarePaginator
    {
        return ExerciseActivityLog::query()
            ->with(['performer'])
            ->where('exercise_id', $exerciseId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): ExerciseActivityLog
    {
        return ExerciseActivityLog::query()->create($data);
    }
}
