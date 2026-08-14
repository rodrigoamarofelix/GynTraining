<?php

namespace App\Repositories;

use App\Enums\WorkoutSessionStatus;
use App\Models\WorkoutSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkoutSessionRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return WorkoutSession::query()
            ->with(['student.user', 'workoutPlan', 'workoutDay', 'exerciseLogs.exercise'])
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($filters['workout_plan_id'] ?? null, fn ($q, $id) => $q->where('workout_plan_id', $id))
            ->when($filters['trainer_id'] ?? null, fn ($q, $id) => $q->whereHas(
                'workoutPlan',
                fn ($planQuery) => $planQuery->where('trainer_id', $id),
            ))
            ->latest('started_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?WorkoutSession
    {
        return WorkoutSession::query()
            ->with([
                'student.user',
                'workoutPlan',
                'workoutDay.exercises.exercise',
                'workoutDay.exercises.sets',
                'sessionExercises.exercise',
                'sessionExercises.logs',
                'exerciseLogs.exercise',
            ])
            ->find($id);
    }

    public function findActiveForStudent(int $studentId): ?WorkoutSession
    {
        return WorkoutSession::query()
            ->where('student_id', $studentId)
            ->where('status', WorkoutSessionStatus::InProgress)
            ->latest('started_at')
            ->first();
    }

    public function findActiveForPlan(int $planId, int $studentId): ?WorkoutSession
    {
        return WorkoutSession::query()
            ->where('workout_plan_id', $planId)
            ->where('student_id', $studentId)
            ->where('status', WorkoutSessionStatus::InProgress)
            ->latest('started_at')
            ->first();
    }

    public function create(array $data): WorkoutSession
    {
        return WorkoutSession::query()->create($data);
    }

    public function update(WorkoutSession $session, array $data): WorkoutSession
    {
        $session->update($data);

        return $session->fresh([
            'student.user',
            'workoutPlan',
            'workoutDay',
            'sessionExercises.exercise',
            'exerciseLogs.exercise',
        ]);
    }

    public function softDelete(WorkoutSession $session): void
    {
        $session->delete();
    }
}
