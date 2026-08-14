<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\Goal;
use App\Models\User;

class GoalPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('progress.view')
            || $this->isAdmin($user)
            || $user->hasAnyRole([RoleName::Trainer, RoleName::Student, RoleName::GymAdmin]);
    }

    public function view(User $user, Goal $goal): bool
    {
        return $this->canAccessStudentData($user, $goal->student_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Student) || $this->isAdmin($user);
    }

    public function update(User $user, Goal $goal): bool
    {
        if ($user->hasRole(RoleName::Student) && $user->student?->id === $goal->student_id) {
            return true;
        }

        return $this->isAdmin($user);
    }

    public function delete(User $user, Goal $goal): bool
    {
        return $this->update($user, $goal);
    }

    protected function canAccessStudentData(User $user, int $studentId): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        if ($user->hasRole(RoleName::Student) && $user->student?->id === $studentId) {
            return true;
        }

        $student = \App\Models\Student::query()->find($studentId);

        if (! $student) {
            return false;
        }

        if ($this->isTrainer($user) && $user->trainer) {
            return $this->trainerCanAccessStudent($user, $student);
        }

        if ($this->isGymAdmin($user)) {
            return $this->managesGym($user, $student->gym_id);
        }

        return false;
    }
}
