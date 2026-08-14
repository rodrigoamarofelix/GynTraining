<?php

namespace App\Services\Dashboard;

use App\Enums\RoleName;
use App\Models\Goal;
use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use App\Repositories\BodyMeasurementRepository;
use App\Repositories\DashboardRepository;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;

class DashboardService
{
    private const INACTIVE_DAYS_THRESHOLD = 7;

    public function __construct(
        private readonly DashboardRepository $repository,
        private readonly BodyMeasurementRepository $measurementRepository,
    ) {}

    public function resolveForUser(User $user): array
    {
        if ($user->hasRole(RoleName::Admin)) {
            return $this->admin();
        }

        if ($user->hasRole(RoleName::GymAdmin)) {
            return $this->gymAdmin($user);
        }

        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            return $this->trainer($user);
        }

        if ($user->hasRole(RoleName::Student) && $user->student) {
            return $this->student($user);
        }

        throw new AuthorizationException('Perfil sem dashboard disponível.');
    }

    public function student(User $user): array
    {
        $studentId = $user->student->id;
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $activePlan = $this->repository->activeWorkoutPlanForStudent($studentId);
        $activeSession = $this->repository->activeSessionForStudent($studentId);
        $lastSession = $this->repository->lastCompletedSessionForStudent($studentId);
        $monthSessions = $this->repository->studentSessionsInPeriod($studentId, $monthStart, $monthEnd);
        $latestMeasurement = $this->measurementRepository->latestForStudent($studentId);
        $weightHistory = $this->measurementRepository->historyForStudent($studentId, 6);
        $goals = $this->repository->activeGoalsForStudent($studentId);

        $nextDay = $this->resolveNextWorkoutDay($activePlan, $lastSession);
        $todayDay = $activeSession?->workoutDay ?? $nextDay;

        $totalDuration = $monthSessions->sum('duration_seconds');
        $weeklyFrequency = $this->weeklyFrequency($studentId);

        return [
            'role' => RoleName::Student->value,
            'today_workout' => $todayDay ? $this->formatWorkoutDay($todayDay, $activePlan) : null,
            'next_workout' => $nextDay ? $this->formatWorkoutDay($nextDay, $activePlan) : null,
            'active_session' => $activeSession ? $this->formatSession($activeSession) : null,
            'last_workout' => $lastSession ? $this->formatSession($lastSession) : null,
            'stats' => [
                'workouts_this_month' => $monthSessions->count(),
                'total_training_seconds' => (int) $totalDuration,
                'weekly_frequency' => $weeklyFrequency,
                'current_weight' => $latestMeasurement?->weight !== null ? (float) $latestMeasurement->weight : null,
                'max_load' => $this->repository->maxLoadForStudent($studentId),
            ],
            'weight_evolution' => $weightHistory->map(fn ($item) => [
                'measured_at' => $item->measured_at?->format('Y-m-d'),
                'weight' => $item->weight !== null ? (float) $item->weight : null,
            ])->values()->all(),
            'goals' => $goals->map(fn (Goal $goal) => [
                'id' => $goal->id,
                'name' => $goal->name,
                'target' => (float) $goal->target,
                'current_value' => (float) $goal->current_value,
                'unit' => $goal->unit,
                'progress_percentage' => $goal->progressPercentage(),
            ])->values()->all(),
        ];
    }

    public function trainer(User $user): array
    {
        $trainerId = $user->trainer->id;
        $gymId = $user->trainer->gym_id;
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $students = $this->repository->studentsForTrainer($trainerId, $gymId);
        $activeStudents = $students->where('status', \App\Enums\ProfileStatus::Active);
        $recentSessions = $this->repository->recentSessionsForTrainer($trainerId);
        $studentsNeedingAttention = $this->studentsNeedingAttention($students);

        return [
            'role' => RoleName::Trainer->value,
            'stats' => [
                'total_students' => $students->count(),
                'active_students' => $activeStudents->count(),
                'workouts_this_month' => $this->repository->completedSessionsForTrainer($trainerId, $monthStart, $monthEnd),
                'students_without_workout' => $studentsNeedingAttention->count(),
            ],
            'recent_workouts' => $recentSessions->map(fn (WorkoutSession $session) => [
                'id' => $session->id,
                'student_name' => $session->student?->user?->name,
                'workout_day' => $session->workoutDay?->name,
                'finished_at' => $session->finished_at?->toISOString(),
                'duration_seconds' => $session->duration_seconds,
            ])->values()->all(),
            'students_needing_attention' => $studentsNeedingAttention->map(fn ($item) => [
                'student_id' => $item['student']->id,
                'student_name' => $item['student']->user?->name,
                'days_since_last_workout' => $item['days_since_last_workout'],
                'last_workout_at' => $item['last_workout_at'],
            ])->values()->all(),
            'alerts' => $studentsNeedingAttention->map(fn ($item) => [
                'type' => 'inactive_student',
                'message' => sprintf(
                    '%s não treina há %d dias.',
                    $item['student']->user?->name ?? 'Aluno',
                    $item['days_since_last_workout'],
                ),
                'student_id' => $item['student']->id,
            ])->values()->all(),
        ];
    }

    public function admin(?array $gymIds = null): array
    {
        return $this->buildAdminDashboard(RoleName::Admin->value, $gymIds);
    }

    public function gymAdmin(User $user): array
    {
        $gymIds = $user->gyms()->pluck('gyms.id')->all();

        return $this->buildAdminDashboard(RoleName::GymAdmin->value, $gymIds ?: [0]);
    }

    private function buildAdminDashboard(string $role, ?array $gymIds): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $recentSessions = $this->repository->recentSessionsGlobal(10, $gymIds);

        return [
            'role' => $role,
            'stats' => [
                'total_users' => $gymIds ? null : $this->repository->countUsers(),
                'total_students' => $this->repository->countStudents($gymIds),
                'active_students' => $this->repository->countActiveStudents($gymIds),
                'total_trainers' => $this->repository->countTrainers($gymIds),
                'total_gyms' => $this->repository->countGyms($gymIds),
                'workouts_this_month' => $this->repository->completedSessionsCount($monthStart, $monthEnd, $gymIds),
                'workouts_total' => $this->repository->completedSessionsCount(gymIds: $gymIds),
            ],
            'recent_workouts' => $recentSessions->map(fn (WorkoutSession $session) => [
                'id' => $session->id,
                'student_name' => $session->student?->user?->name,
                'workout_day' => $session->workoutDay?->name,
                'finished_at' => $session->finished_at?->toISOString(),
            ])->values()->all(),
        ];
    }

    private function resolveNextWorkoutDay(?WorkoutPlan $plan, ?WorkoutSession $lastSession): ?WorkoutDay
    {
        if (! $plan || $plan->days->isEmpty()) {
            return null;
        }

        $days = $plan->days->sortBy('order')->values();

        if (! $lastSession || ! $lastSession->workout_day_id) {
            return $days->first();
        }

        $lastIndex = $days->search(fn (WorkoutDay $day) => $day->id === $lastSession->workout_day_id);

        if ($lastIndex === false) {
            return $days->first();
        }

        $nextIndex = ($lastIndex + 1) % $days->count();

        return $days[$nextIndex];
    }

    private function weeklyFrequency(int $studentId): float
    {
        $from = now()->subWeeks(4)->startOfDay();
        $sessions = $this->repository->studentSessionsInPeriod($studentId, $from, now());

        return round($sessions->count() / 4, 2);
    }

    private function studentsNeedingAttention(Collection $students): Collection
    {
        return $students->map(function ($student) {
            $lastSession = $this->repository->lastSessionForStudent($student->id);

            if (! $lastSession?->finished_at) {
                return [
                    'student' => $student,
                    'days_since_last_workout' => null,
                    'last_workout_at' => null,
                ];
            }

            $days = (int) $lastSession->finished_at->diffInDays(now());

            return [
                'student' => $student,
                'days_since_last_workout' => $days,
                'last_workout_at' => $lastSession->finished_at->toISOString(),
            ];
        })->filter(function (array $item) {
            if ($item['days_since_last_workout'] === null) {
                return true;
            }

            return $item['days_since_last_workout'] >= self::INACTIVE_DAYS_THRESHOLD;
        });
    }

    private function formatWorkoutDay(WorkoutDay $day, ?WorkoutPlan $plan): array
    {
        return [
            'id' => $day->id,
            'workout_plan_id' => $plan?->id,
            'workout_plan_name' => $plan?->name,
            'name' => $day->name,
            'order' => $day->order,
        ];
    }

    private function formatSession(WorkoutSession $session): array
    {
        return [
            'id' => $session->id,
            'workout_plan_id' => $session->workout_plan_id,
            'workout_day_id' => $session->workout_day_id,
            'workout_day_name' => $session->workoutDay?->name,
            'started_at' => $session->started_at?->toISOString(),
            'finished_at' => $session->finished_at?->toISOString(),
            'duration_seconds' => $session->duration_seconds,
            'status' => $session->status?->value,
        ];
    }
}
