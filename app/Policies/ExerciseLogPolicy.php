<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\ExerciseLog;
use App\Models\User;

class ExerciseLogPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('progress.view')
            || $user->hasPermission('workouts.execute')
            || $this->isAdmin($user)
            || $user->hasAnyRole([RoleName::Trainer, RoleName::Student, RoleName::GymAdmin]);
    }

    public function view(User $user, ExerciseLog $log): bool
    {
        return $this->canAccessLog($user, $log);
    }

    protected function canAccessLog(User $user, ExerciseLog $log): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole(RoleName::Student) && $user->student?->id === $log->student_id) {
            return true;
        }

        $log->loadMissing(['student', 'workoutSession.workoutPlan']);

        if ($this->isTrainer($user) && $user->trainer?->id === $log->workoutSession?->workoutPlan?->trainer_id) {
            return true;
        }

        if ($this->isGymAdmin($user) && $log->student) {
            return $this->managesGym($user, $log->student->gym_id);
        }

        return false;
    }
}
