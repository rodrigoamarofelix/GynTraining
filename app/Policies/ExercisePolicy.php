<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Exercise;
use App\Models\User;

class ExercisePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('exercises.manage')
            || $user->hasPermission('workouts.execute')
            || $this->isAdmin($user)
            || $this->isTrainer($user)
            || $user->hasRole(RoleName::Student);
    }

    public function view(User $user, Exercise $exercise): bool
    {
        if ($exercise->gym_id === null) {
            return $this->viewAny($user);
        }

        return $this->belongsToGym($user, $exercise->gym_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('exercises.manage') || $this->isAdmin($user) || $this->isGymAdmin($user);
    }

    public function update(User $user, Exercise $exercise): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($exercise->gym_id === null) {
            return $this->isAdmin($user);
        }

        return $this->managesGym($user, $exercise->gym_id);
    }

    public function delete(User $user, Exercise $exercise): bool
    {
        return $this->update($user, $exercise);
    }
}
