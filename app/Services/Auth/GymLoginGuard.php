<?php

namespace App\Services\Auth;

use App\Enums\GymStatus;
use App\Enums\RoleName;
use App\Models\Gym;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class GymLoginGuard
{
    public function ensureGymAcceptsRegistration(int $gymId): void
    {
        $this->assertGymIsAccessible(
            $this->findGym($gymId),
            'gym_id',
            'A academia selecionada não está disponível para cadastro.',
        );
    }

    public function ensureUserCanLogin(User $user): void
    {
        $user->loadMissing(['student', 'trainer', 'roles']);

        if ($user->hasRole(RoleName::Admin)) {
            return;
        }

        if ($user->trainer) {
            $this->assertGymIsAccessible(
                $this->findGym($user->trainer->gym_id),
                'email',
                'A academia está inativa ou indisponível. Entre em contato com o administrador.',
            );

            return;
        }

        if ($user->student) {
            $this->assertGymIsAccessible(
                $this->findGym($user->student->gym_id),
                'email',
                'A academia está inativa ou indisponível. Entre em contato com o administrador.',
            );

            return;
        }

        if ($user->hasRole(RoleName::GymAdmin)) {
            $hasActiveGym = $user->gyms()
                ->where('status', GymStatus::Active)
                ->whereNull('gyms.deleted_at')
                ->exists();

            if (! $hasActiveGym) {
                throw ValidationException::withMessages([
                    'email' => ['Nenhuma academia ativa vinculada à sua conta.'],
                ]);
            }
        }
    }

    private function findGym(?int $gymId): ?Gym
    {
        if (! $gymId) {
            return null;
        }

        return Gym::withTrashed()->find($gymId);
    }

    private function assertGymIsAccessible(?Gym $gym, string $field, string $message): void
    {
        if (! $gym || $gym->trashed() || $gym->status !== GymStatus::Active) {
            throw ValidationException::withMessages([
                $field => [$message],
            ]);
        }
    }
}
