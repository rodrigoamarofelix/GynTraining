<?php

namespace App\Policies;

use App\Models\ExerciseCategory;
use App\Models\User;

class ExerciseCategoryPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, ExerciseCategory $exerciseCategory): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('exercises.manage') || $this->isAdmin($user);
    }

    public function update(User $user, ExerciseCategory $exerciseCategory): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, ExerciseCategory $exerciseCategory): bool
    {
        return $this->isAdmin($user);
    }
}
