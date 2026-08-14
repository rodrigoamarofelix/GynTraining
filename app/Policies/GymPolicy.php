<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Gym;
use App\Models\User;

class GymPolicy
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

    public function view(User $user, Gym $gym): bool
    {
        return $this->belongsToGym($user, $gym->id) || $this->isAdmin($user);
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Gym $gym): bool
    {
        return $this->managesGym($user, $gym->id);
    }

    public function delete(User $user, Gym $gym): bool
    {
        return $this->isAdmin($user);
    }
}
