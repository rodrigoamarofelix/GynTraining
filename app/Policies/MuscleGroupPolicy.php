<?php

namespace App\Policies;

use App\Models\MuscleGroup;
use App\Models\User;

class MuscleGroupPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, MuscleGroup $muscleGroup): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('exercises.manage') || $this->isAdmin($user);
    }

    public function update(User $user, MuscleGroup $muscleGroup): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, MuscleGroup $muscleGroup): bool
    {
        return $this->isAdmin($user);
    }
}
