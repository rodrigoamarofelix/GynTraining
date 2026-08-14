<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Student;
use App\Models\User;

class StudentPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([
            RoleName::Admin,
            RoleName::GymAdmin,
            RoleName::Trainer,
        ]) || $user->hasRole(RoleName::Student);
    }

    public function view(User $user, Student $student): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole(RoleName::Student)) {
            return $user->id === $student->user_id;
        }

        return $this->belongsToGym($user, $student->gym_id);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission('users.manage')
            || $this->isAdmin($user)
            || $this->isGymAdmin($user);
    }

    public function update(User $user, Student $student): bool
    {
        if ($this->managesGym($user, $student->gym_id)) {
            return true;
        }

        return $user->hasRole(RoleName::Student) && $user->id === $student->user_id;
    }

    public function delete(User $user, Student $student): bool
    {
        return $this->managesGym($user, $student->gym_id);
    }
}
