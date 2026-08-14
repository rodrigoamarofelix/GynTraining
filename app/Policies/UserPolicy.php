<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Admin,
            RoleName::GymAdmin,
        ]);
    }

    public function view(User $user, User $model): bool
    {
        if ($user->hasAnyRole([RoleName::Admin, RoleName::GymAdmin])) {
            return true;
        }

        if ($user->hasRole(RoleName::Trainer)) {
            return $model->hasRole(RoleName::Student);
        }

        return $user->id === $model->id;
    }

    public function update(User $user, User $model): bool
    {
        if ($user->hasAnyRole([RoleName::Admin, RoleName::GymAdmin])) {
            return true;
        }

        return $user->id === $model->id;
    }

    public function delete(User $user, User $model): bool
    {
        return $user->hasRole(RoleName::Admin);
    }
}
