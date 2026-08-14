<?php

namespace App\Policies;

use App\Enums\RoleName;
use App\Models\BodyMeasurement;
use App\Models\User;

class BodyMeasurementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission('progress.view')
            || $this->isAdmin($user)
            || $user->hasAnyRole([RoleName::Trainer, RoleName::Student, RoleName::GymAdmin]);
    }

    public function view(User $user, BodyMeasurement $measurement): bool
    {
        return $this->canAccessStudentData($user, $measurement->student_id);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(RoleName::Student)
            || $this->isAdmin($user)
            || $this->isTrainer($user);
    }

    public function update(User $user, BodyMeasurement $measurement): bool
    {
        if ($user->hasRole(RoleName::Student) && $user->student?->id === $measurement->student_id) {
            return true;
        }

        return $this->isAdmin($user);
    }

    public function delete(User $user, BodyMeasurement $measurement): bool
    {
        return $this->update($user, $measurement);
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
