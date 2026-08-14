<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;
use App\Models\WorkoutSession;

class WorkoutSessionPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('workouts.execute')
            || $user->hasPermission('workouts.manage')
            || $user->hasPermission('progress.view')
            || $this->isAdmin($user)
            || $user->hasAnyRole([RoleName::Trainer, RoleName::Student, RoleName::GymAdmin]);
    }

    public function view(User $user, WorkoutSession $session): bool
    {
        return $this->canAccessSession($user, $session);
    }

    public function start(User $user): bool
    {
        return $user->hasPermission('workouts.execute')
            || $this->isAdmin($user)
            || $user->hasRole(RoleName::Student);
    }

    public function finish(User $user, WorkoutSession $session): bool
    {
        if ($user->hasRole(RoleName::Student) && $user->student?->id === $session->student_id) {
            return true;
        }

        return $this->canAccessSession($user, $session);
    }

    public function logSet(User $user, WorkoutSession $session): bool
    {
        if ($user->hasRole(RoleName::Student) && $user->student?->id === $session->student_id) {
            return true;
        }

        return false;
    }

    protected function canAccessSession(User $user, WorkoutSession $session): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole(RoleName::Student) && $user->student?->id === $session->student_id) {
            return true;
        }

        $session->loadMissing(['workoutPlan', 'student']);

        if ($this->isTrainer($user) && $user->trainer?->id === $session->workoutPlan?->trainer_id) {
            return true;
        }

        if ($this->isGymAdmin($user) && $session->student) {
            return $this->managesGym($user, $session->student->gym_id);
        }

        return false;
    }
}
