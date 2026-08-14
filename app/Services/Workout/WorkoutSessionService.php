<?php

namespace App\Services\Workout;

use App\Enums\RoleName;
use App\Enums\WorkoutSessionStatus;
use App\Models\ExerciseLog;
use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use App\Models\WorkoutSessionExercise;
use App\Repositories\ExerciseLogRepository;
use App\Repositories\WorkoutSessionRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkoutSessionService
{
    public function __construct(
        private readonly WorkoutSessionRepository $sessionRepository,
        private readonly ExerciseLogRepository $logRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->sessionRepository->paginate($filters, $perPage);
    }

    public function find(int $id): ?WorkoutSession
    {
        return $this->sessionRepository->findById($id);
    }

    public function start(WorkoutPlan $plan, WorkoutDay $day, User $user): WorkoutSession
    {
        if ($day->workout_plan_id !== $plan->id) {
            throw ValidationException::withMessages([
                'workout_day_id' => ['O dia de treino não pertence a esta ficha.'],
            ]);
        }

        $student = $user->student;

        if (! $student || $student->id !== $plan->student_id) {
            throw ValidationException::withMessages([
                'workout' => ['Somente o aluno da ficha pode iniciar este treino.'],
            ]);
        }

        if ($this->sessionRepository->findActiveForStudent($student->id)) {
            throw ValidationException::withMessages([
                'session' => ['Já existe um treino em andamento. Finalize-o antes de iniciar outro.'],
            ]);
        }

        return DB::transaction(function () use ($plan, $day, $student) {
            $session = $this->sessionRepository->create([
                'student_id' => $student->id,
                'workout_plan_id' => $plan->id,
                'workout_day_id' => $day->id,
                'started_at' => now(),
                'status' => WorkoutSessionStatus::InProgress,
            ]);

            $day->load(['exercises.exercise']);

            foreach ($day->exercises as $workoutExercise) {
                WorkoutSessionExercise::query()->create([
                    'workout_session_id' => $session->id,
                    'exercise_id' => $workoutExercise->exercise_id,
                    'workout_exercise_id' => $workoutExercise->id,
                    'order' => $workoutExercise->order,
                ]);
            }

            return $this->sessionRepository->findById($session->id);
        });
    }

    public function finish(WorkoutSession $session, ?string $notes = null): WorkoutSession
    {
        if (! $session->isInProgress()) {
            throw ValidationException::withMessages([
                'session' => ['Este treino não está em andamento.'],
            ]);
        }

        $finishedAt = now();
        $duration = (int) round($session->started_at->diffInSeconds($finishedAt));

        return $this->sessionRepository->update($session, [
            'finished_at' => $finishedAt,
            'duration_seconds' => $duration,
            'status' => WorkoutSessionStatus::Completed,
            'notes' => $notes ?? $session->notes,
        ]);
    }

    public function cancel(WorkoutSession $session, ?string $notes = null): WorkoutSession
    {
        if (! $session->isInProgress()) {
            throw ValidationException::withMessages([
                'session' => ['Este treino não está em andamento.'],
            ]);
        }

        return $this->sessionRepository->update($session, [
            'finished_at' => now(),
            'status' => WorkoutSessionStatus::Cancelled,
            'notes' => $notes ?? $session->notes,
        ]);
    }

    public function logSet(WorkoutSession $session, array $data): ExerciseLog
    {
        if (! $session->isInProgress()) {
            throw ValidationException::withMessages([
                'session' => ['Não é possível registrar séries em um treino finalizado.'],
            ]);
        }

        return DB::transaction(function () use ($session, $data) {
            $sessionExercise = WorkoutSessionExercise::query()
                ->where('workout_session_id', $session->id)
                ->where('exercise_id', $data['exercise_id'])
                ->with('workoutExercise')
                ->first();

            if ($sessionExercise && ! $sessionExercise->started_at) {
                $sessionExercise->update(['started_at' => now()]);
            }

            $plannedRest = $data['rest_time'] ?? null;

            if ($plannedRest === null && isset($data['workout_exercise_id'])) {
                $workoutExercise = \App\Models\WorkoutExercise::query()->find($data['workout_exercise_id']);
                $plannedRest = $workoutExercise?->rest_time;
            }

            if ($plannedRest === null) {
                $plannedRest = $sessionExercise?->workoutExercise?->rest_time;
            }

            $log = $this->logRepository->create([
                'workout_session_id' => $session->id,
                'workout_session_exercise_id' => $sessionExercise?->id,
                'exercise_id' => $data['exercise_id'],
                'student_id' => $session->student_id,
                'set_number' => $data['set_number'],
                'repetitions' => $data['repetitions'] ?? null,
                'load' => $data['load'] ?? null,
                'rest_time' => $plannedRest,
                'duration' => $data['duration'] ?? null,
                'notes' => $data['notes'] ?? null,
                'logged_at' => now(),
            ]);

            return $log->load(['exercise', 'workoutSession']);
        });
    }

    public function finishActiveForPlan(WorkoutPlan $plan, User $user, ?string $notes = null): WorkoutSession
    {
        $student = $user->student;

        if (! $student || $student->id !== $plan->student_id) {
            throw ValidationException::withMessages([
                'workout' => ['Somente o aluno da ficha pode finalizar este treino.'],
            ]);
        }

        $session = $this->sessionRepository->findActiveForPlan($plan->id, $student->id);

        if (! $session) {
            throw ValidationException::withMessages([
                'session' => ['Não há treino em andamento para esta ficha.'],
            ]);
        }

        return $this->finish($session, $notes);
    }

    public function history(int $studentId, ?int $exerciseId = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->logRepository->historyForStudent($studentId, $exerciseId, $perPage);
    }

    public function filtersForUser(User $user): array
    {
        if ($user->hasRole(RoleName::Admin)) {
            return [];
        }

        if ($user->hasRole(RoleName::Student) && $user->student) {
            return ['student_id' => $user->student->id];
        }

        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            return ['trainer_id' => $user->trainer->id];
        }

        return ['student_id' => 0];
    }
}
