<?php

namespace App\Services\Progress;

use App\Enums\RoleName;
use App\Models\Student;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class StudentProgressAccess
{
    public function resolveStudentId(User $user, ?int $requestedStudentId = null): int
    {
        if ($user->hasRole(RoleName::Student) && $user->student) {
            if ($requestedStudentId && $requestedStudentId !== $user->student->id) {
                throw new AuthorizationException;
            }

            return $user->student->id;
        }

        if (! $requestedStudentId) {
            throw new AuthorizationException('Informe o aluno para consultar os dados.');
        }

        $student = Student::query()->findOrFail($requestedStudentId);

        if ($user->hasRole(RoleName::Admin)) {
            return $student->id;
        }

        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            if ($student->gym_id !== $user->trainer->gym_id) {
                throw new AuthorizationException;
            }

            $allowed = $student->trainer_id === $user->trainer->id
                || $student->workoutPlans()->where('trainer_id', $user->trainer->id)->exists();

            if (! $allowed) {
                throw new AuthorizationException;
            }

            return $student->id;
        }

        if ($user->hasRole(RoleName::GymAdmin)) {
            if (! $user->gyms()->where('gyms.id', $student->gym_id)->exists()) {
                throw new AuthorizationException;
            }

            return $student->id;
        }

        throw new AuthorizationException;
    }

    public function filtersForUser(User $user): array
    {
        if ($user->hasRole(RoleName::Admin)) {
            return [];
        }

        if ($user->hasRole(RoleName::Student) && $user->student) {
            return ['student_id' => $user->student->id];
        }

        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            return ['trainer_id' => $user->trainer->id];
        }

        return ['student_id' => 0];
    }
}
