<?php

namespace App\Services\Workout;

use App\Enums\RoleName;
use App\Enums\WorkoutPlanStatus;
use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutExercise;
use App\Models\Student;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSet;
use App\Repositories\WorkoutPlanRepository;
use App\Services\Notification\AppNotificationService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WorkoutPlanService
{
    public function __construct(
        private readonly WorkoutPlanRepository $workoutPlanRepository,
        private readonly AppNotificationService $notificationService,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->workoutPlanRepository->paginate($filters, $perPage);
    }

    public function find(int $id, bool $withTrashed = false): ?WorkoutPlan
    {
        return $this->workoutPlanRepository->findById($id, $withTrashed);
    }

    public function create(array $data): WorkoutPlan
    {
        $data['status'] = $data['status'] ?? WorkoutPlanStatus::Active->value;

        return DB::transaction(function () use ($data) {
            $this->ensureStudentTrainerAssignment($data['student_id'], $data['trainer_id'] ?? null);

            $plan = $this->workoutPlanRepository->create($data);

            if (! empty($data['days']) && is_array($data['days'])) {
                $this->syncDays($plan, $data['days']);
            }

            return $this->workoutPlanRepository->findById($plan->id);
        });
    }

    public function update(WorkoutPlan $plan, array $data): WorkoutPlan
    {
        return DB::transaction(function () use ($plan, $data) {
            $days = $data['days'] ?? null;
            unset($data['days']);

            if ($data !== []) {
                $this->workoutPlanRepository->update($plan, $data);
            }

            if (is_array($days)) {
                $this->replaceDays($plan->fresh(), $days);
            }

            $updatedPlan = $this->workoutPlanRepository->findById($plan->id);
            $this->notificationService->notifyWorkoutPlanUpdated($updatedPlan);

            return $updatedPlan;
        });
    }

    public function delete(WorkoutPlan $plan): void
    {
        DB::transaction(function () use ($plan) {
            $plan->load(['days.exercises.sets']);

            foreach ($plan->days as $day) {
                $this->softDeleteDayTree($day);
            }

            $this->workoutPlanRepository->softDelete($plan);
        });
    }

    public function restore(int $planId): WorkoutPlan
    {
        return DB::transaction(function () use ($planId) {
            $plan = $this->workoutPlanRepository->findById($planId, true);

            if (! $plan || ! $plan->trashed()) {
                abort(404, 'Ficha não encontrada ou não está excluída.');
            }

            WorkoutDay::withTrashed()
                ->where('workout_plan_id', $plan->id)
                ->each(function (WorkoutDay $day) {
                    WorkoutExercise::withTrashed()
                        ->where('workout_day_id', $day->id)
                        ->each(function (WorkoutExercise $workoutExercise) {
                            $workoutExercise->sets()->withTrashed()->each(fn (WorkoutSet $set) => $set->restore());
                            $workoutExercise->restore();
                        });

                    $day->restore();
                });

            return $this->workoutPlanRepository->restore($plan);
        });
    }

    public function filtersForUser(User $user): array
    {
        if ($user->hasRole(RoleName::Admin)) {
            return [];
        }

        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            return ['trainer_id' => $user->trainer->id];
        }

        if ($user->hasRole(RoleName::Student) && $user->student) {
            return ['student_id' => $user->student->id];
        }

        return ['student_id' => 0];
    }

    private function replaceDays(WorkoutPlan $plan, array $days): void
    {
        $plan->load(['days.exercises.sets']);

        foreach ($plan->days as $day) {
            $this->softDeleteDayTree($day);
        }

        $this->syncDays($plan->fresh(), $days);
    }

    private function ensureStudentTrainerAssignment(int $studentId, ?int $trainerId): void
    {
        if (! $trainerId) {
            return;
        }

        $student = Student::query()->find($studentId);

        if (! $student || $student->trainer_id !== null) {
            return;
        }

        $student->update(['trainer_id' => $trainerId]);
    }

    private function syncDays(WorkoutPlan $plan, array $days): void
    {
        foreach ($days as $dayData) {
            $day = $plan->days()->create([
                'name' => $dayData['name'],
                'description' => $dayData['description'] ?? null,
                'order' => $dayData['order'] ?? 1,
            ]);

            foreach ($dayData['exercises'] ?? [] as $exerciseData) {
                $workoutExercise = $day->exercises()->create([
                    'exercise_id' => $exerciseData['exercise_id'],
                    'order' => $exerciseData['order'] ?? 1,
                    'notes' => $exerciseData['notes'] ?? null,
                    'execution_time' => $exerciseData['execution_time'] ?? null,
                    'rest_time' => $exerciseData['rest_time'] ?? null,
                ]);

                foreach ($exerciseData['sets'] ?? [] as $setData) {
                    $workoutExercise->sets()->create([
                        'set_number' => $setData['set_number'],
                        'repetitions' => $setData['repetitions'] ?? null,
                        'load' => $setData['load'] ?? null,
                        'rest_time' => $setData['rest_time'] ?? null,
                        'duration' => $setData['duration'] ?? null,
                        'notes' => $setData['notes'] ?? null,
                    ]);
                }
            }
        }
    }

    private function softDeleteDayTree(WorkoutDay $day): void
    {
        $day->load(['exercises.sets']);

        foreach ($day->exercises as $exercise) {
            $exercise->sets->each(fn (WorkoutSet $set) => $set->delete());
            $exercise->delete();
        }

        $day->delete();
    }
}
