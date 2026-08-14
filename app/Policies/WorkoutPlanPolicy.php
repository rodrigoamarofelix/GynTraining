<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\WorkoutPlan;

class WorkoutPlanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('workouts.manage')
            || $user->hasPermission('workouts.execute')
            || $this->isAdmin($user)
            || $user->hasAnyRole([RoleName::Trainer, RoleName::Student, RoleName::GymAdmin]);
    }

    public function view(User $user, WorkoutPlan $workoutPlan): bool
    {
        return $this->canAccessWorkoutPlan($user, $workoutPlan);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('workouts.manage')
            || $this->isAdmin($user)
            || $this->isTrainer($user)
            || $this->isGymAdmin($user);
    }

    public function update(User $user, WorkoutPlan $workoutPlan): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($this->isTrainer($user) && $user->trainer?->id === $workoutPlan->trainer_id) {
            return true;
        }

        return $this->isGymAdmin($user) && $this->canAccessWorkoutPlan($user, $workoutPlan);
    }

    public function delete(User $user, WorkoutPlan $workoutPlan): bool
    {
        return $this->update($user, $workoutPlan);
    }

    protected function canAccessWorkoutPlan(User $user, WorkoutPlan $workoutPlan): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole(RoleName::Student) && $user->student?->id === $workoutPlan->student_id) {
            return true;
        }

        if ($this->isTrainer($user) && $user->trainer?->id === $workoutPlan->trainer_id) {
            return true;
        }

        if ($this->isGymAdmin($user)) {
            $workoutPlan->loadMissing('student');

            return $workoutPlan->student && $this->managesGym($user, $workoutPlan->student->gym_id);
        }

        return false;
    }
}
