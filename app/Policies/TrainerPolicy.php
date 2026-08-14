<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Trainer;
use App\Models\User;

class TrainerPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Admin,
            RoleName::GymAdmin,
            RoleName::Trainer,
            RoleName::Student,
        ]);
    }

    public function view(User $user, Trainer $trainer): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole(RoleName::Trainer) && $user->id === $trainer->user_id) {
            return true;
        }

        return $this->belongsToGym($user, $trainer->gym_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage')
            || $this->isAdmin($user)
            || $this->isGymAdmin($user);
    }

    public function update(User $user, Trainer $trainer): bool
    {
        if ($this->managesGym($user, $trainer->gym_id)) {
            return true;
        }

        return $user->hasRole(RoleName::Trainer) && $user->id === $trainer->user_id;
    }

    public function delete(User $user, Trainer $trainer): bool
    {
        return $this->managesGym($user, $trainer->gym_id);
    }
}
