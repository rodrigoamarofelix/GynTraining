<?php

namespace App\Repositories;

use App\Models\ExerciseLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ExerciseLogRepository
{
    public function create(array $data): ExerciseLog
    {
        return ExerciseLog::query()->create($data);
    }

    public function historyForStudent(int $studentId, ?int $exerciseId = null, int $perPage = 20): LengthAwarePaginator
    {
        return ExerciseLog::query()
            ->with(['exercise', 'workoutSession'])
            ->where('student_id', $studentId)
            ->when($exerciseId, fn ($q, $id) => $q->where('exercise_id', $id))
            ->latest('logged_at')
            ->paginate($perPage);
    }

    public function historyGroupedByExercise(int $studentId): Collection
    {
        return ExerciseLog::query()
            ->with('exercise')
            ->where('student_id', $studentId)
            ->latest('logged_at')
            ->get()
            ->groupBy('exercise_id');
    }
}
