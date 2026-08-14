<?php

namespace App\Repositories;

use App\Enums\GoalStatus;
use App\Enums\ProfileStatus;
use App\Enums\WorkoutPlanStatus;
use App\Enums\WorkoutSessionStatus;
use App\Models\ExerciseLog;
use App\Models\Goal;
use App\Models\Gym;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository
{
    public function activeWorkoutPlanForStudent(int $studentId): ?WorkoutPlan
    {
        return WorkoutPlan::query()
            ->with(['days' => fn ($q) => $q->orderBy('order')])
            ->where('student_id', $studentId)
            ->where('status', WorkoutPlanStatus::Active)
            ->latest('updated_at')
            ->first();
    }

    public function activeSessionForStudent(int $studentId): ?WorkoutSession
    {
        return WorkoutSession::query()
            ->with(['workoutDay', 'workoutPlan'])
            ->where('student_id', $studentId)
            ->where('status', WorkoutSessionStatus::InProgress)
            ->latest('started_at')
            ->first();
    }

    public function lastCompletedSessionForStudent(int $studentId): ?WorkoutSession
    {
        return WorkoutSession::query()
            ->with(['workoutDay', 'workoutPlan'])
            ->where('student_id', $studentId)
            ->where('status', WorkoutSessionStatus::Completed)
            ->latest('finished_at')
            ->first();
    }

    public function studentSessionsInPeriod(int $studentId, Carbon $from, Carbon $to): Collection
    {
        return WorkoutSession::query()
            ->where('student_id', $studentId)
            ->where('status', WorkoutSessionStatus::Completed)
            ->whereBetween('started_at', [$from, $to])
            ->get();
    }

    public function activeGoalsForStudent(int $studentId, int $limit = 5): Collection
    {
        return Goal::query()
            ->where('student_id', $studentId)
            ->where('status', GoalStatus::Active)
            ->latest('created_at')
            ->limit($limit)
            ->get();
    }

    public function maxLoadForStudent(int $studentId): ?float
    {
        $max = ExerciseLog::query()->where('student_id', $studentId)->max('load');

        return $max !== null ? (float) $max : null;
    }

    public function studentsForTrainer(int $trainerId, int $gymId): Collection
    {
        return Student::query()
            ->with('user')
            ->where('gym_id', $gymId)
            ->where('status', ProfileStatus::Active)
            ->where(function ($query) use ($trainerId) {
                $query->where('trainer_id', $trainerId)
                    ->orWhereHas('workoutPlans', fn ($planQuery) => $planQuery->where('trainer_id', $trainerId));
            })
            ->get();
    }

    public function lastSessionForStudent(int $studentId): ?WorkoutSession
    {
        return WorkoutSession::query()
            ->with(['student.user', 'workoutDay'])
            ->where('student_id', $studentId)
            ->where('status', WorkoutSessionStatus::Completed)
            ->latest('finished_at')
            ->first();
    }

    public function completedSessionsForTrainer(int $trainerId, Carbon $from, Carbon $to): int
    {
        return WorkoutSession::query()
            ->where('status', WorkoutSessionStatus::Completed)
            ->whereBetween('started_at', [$from, $to])
            ->whereHas('workoutPlan', fn ($q) => $q->where('trainer_id', $trainerId))
            ->count();
    }

    public function recentSessionsForTrainer(int $trainerId, int $limit = 10): Collection
    {
        return WorkoutSession::query()
            ->with(['student.user', 'workoutDay', 'workoutPlan'])
            ->where('status', WorkoutSessionStatus::Completed)
            ->whereHas('workoutPlan', fn ($q) => $q->where('trainer_id', $trainerId))
            ->latest('finished_at')
            ->limit($limit)
            ->get();
    }

    public function countUsers(): int
    {
        return User::query()->count();
    }

    public function countStudents(?array $gymIds = null): int
    {
        return Student::query()
            ->when($gymIds, fn ($q) => $q->whereIn('gym_id', $gymIds))
            ->count();
    }

    public function countActiveStudents(?array $gymIds = null): int
    {
        return Student::query()
            ->where('status', ProfileStatus::Active)
            ->when($gymIds, fn ($q) => $q->whereIn('gym_id', $gymIds))
            ->count();
    }

    public function countTrainers(?array $gymIds = null): int
    {
        return Trainer::query()
            ->when($gymIds, fn ($q) => $q->whereIn('gym_id', $gymIds))
            ->count();
    }

    public function countGyms(?array $gymIds = null): int
    {
        return Gym::query()
            ->when($gymIds, fn ($q) => $q->whereIn('id', $gymIds))
            ->count();
    }

    public function completedSessionsCount(?Carbon $from = null, ?Carbon $to = null, ?array $gymIds = null): int
    {
        return WorkoutSession::query()
            ->where('status', WorkoutSessionStatus::Completed)
            ->when($from && $to, fn ($q) => $q->whereBetween('started_at', [$from, $to]))
            ->when($gymIds, fn ($q) => $q->whereHas('student', fn ($s) => $s->whereIn('gym_id', $gymIds)))
            ->count();
    }

    public function recentSessionsGlobal(int $limit = 10, ?array $gymIds = null): Collection
    {
        return WorkoutSession::query()
            ->with(['student.user', 'workoutDay'])
            ->where('status', WorkoutSessionStatus::Completed)
            ->when($gymIds, fn ($q) => $q->whereHas('student', fn ($s) => $s->whereIn('gym_id', $gymIds)))
            ->latest('finished_at')
            ->limit($limit)
            ->get();
    }
}
