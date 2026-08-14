<?php

namespace App\Support;

use App\Enums\RoleName;
use App\Models\User;

class ManagedGymScope
{
    public static function idsFor(User $user): ?array
    {
        if ($user->hasRole(RoleName::Admin)) {
            return null;
        }

        if ($user->hasRole(RoleName::GymAdmin)) {
            $ids = $user->gyms()->pluck('gyms.id')->all();

            return $ids === [] ? [0] : $ids;
        }

        return [0];
    }

    public static function manages(User $user, int $gymId): bool
    {
        $ids = self::idsFor($user);

        if ($ids === null) {
            return true;
        }

        return in_array($gymId, $ids, true);
    }

    public static function filtersFor(User $user): array
    {
        $ids = self::idsFor($user);

        if ($ids === null) {
            return [];
        }

        return ['managed_gym_ids' => $ids];
    }
}
