<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\User;

class DashboardPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Admin,
            RoleName::GymAdmin,
            RoleName::Trainer,
            RoleName::Student,
        ]);
    }
}
