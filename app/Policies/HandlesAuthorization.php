<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

trait HandlesAuthorization
{
    protected function isAdmin(User $user): bool
    {
        return $user->hasRole(RoleName::Admin);
    }

    protected function isGymAdmin(User $user): bool
    {
        return $user->hasRole(RoleName::GymAdmin);
    }

    protected function isTrainer(User $user): bool
    {
        return $user->hasRole(RoleName::Trainer);
    }

    protected function managesGym(User $user, int $gymId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $this->isGymAdmin($user) && $user->gyms()->where('gyms.id', $gymId)->exists();
    }

    protected function belongsToGym(User $user, int $gymId): bool
    {
        if ($this->managesGym($user, $gymId)) {
            return true;
        }

        if ($this->isTrainer($user) && $user->trainer?->gym_id === $gymId) {
            return true;
        }

        if ($user->hasRole(RoleName::Student) && $user->student?->gym_id === $gymId) {
            return true;
        }

        return $user->gyms()->where('gyms.id', $gymId)->exists();
    }

    protected function trainerCanAccessStudent(User $user, \App\Models\Student $student): bool
    {
        if (! $this->isTrainer($user) || ! $user->trainer) {
            return false;
        }

        if ($student->gym_id !== $user->trainer->gym_id) {
            return false;
        }

        return $student->trainer_id === $user->trainer->id
            || $student->workoutPlans()->where('trainer_id', $user->trainer->id)->exists();
    }
}
