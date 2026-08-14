<?php

namespace App\Repositories;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class UserRepository
{
    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function findByEmail(string $email): ?User
    {
        return User::query()->where('email', $email)->first();
    }

    public function findById(int $id): ?User
    {
        return User::query()->with('roles')->find($id);
    }

    public function assignRole(User $user, RoleName $role): void
    {
        $user->assignRole($role);
    }

    public function markAsActive(User $user): User
    {
        $user->update(['status' => UserStatus::Active]);

        return $user->fresh(['roles']);
    }

    public function getUsersByRole(RoleName $role): Collection
    {
        return User::query()
            ->whereHas('roles', fn ($query) => $query->where('slug', $role->value))
            ->with('roles')
            ->get();
    }
}
