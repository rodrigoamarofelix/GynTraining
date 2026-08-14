<?php

namespace App\Policies;

use App\Enums\ProgressPhotoVisibility;
use App\Enums\RoleName;
use App\Models\ProgressPhoto;
use App\Models\User;

class ProgressPhotoPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('progress.view')
            || $this->isAdmin($user)
            || $user->hasAnyRole([RoleName::Trainer, RoleName::Student, RoleName::GymAdmin]);
    }

    public function view(User $user, ProgressPhoto $photo): bool
    {
        if ($user->hasRole(RoleName::Student) && $user->student?->id === $photo->student_id) {
            return true;
        }

        if ($this->isAdmin($user)) {
            return true;
        }

        if ($photo->visibility === ProgressPhotoVisibility::Private) {
            return false;
        }

        $student = $photo->student ?? $photo->load('student')->student;

        if ($photo->visibility === ProgressPhotoVisibility::Trainer && $this->isTrainer($user) && $user->trainer && $student) {
            return $this->trainerCanAccessStudent($user, $student);
        }

        if ($photo->visibility === ProgressPhotoVisibility::Gym && $this->isGymAdmin($user) && $student) {
            return $this->managesGym($user, $student->gym_id);
        }

        return false;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Student) || $this->isAdmin($user);
    }

    public function update(User $user, ProgressPhoto $photo): bool
    {
        return $user->hasRole(RoleName::Student) && $user->student?->id === $photo->student_id
            || $this->isAdmin($user);
    }

    public function delete(User $user, ProgressPhoto $photo): bool
    {
        return $this->update($user, $photo);
    }
}
