<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $adminRoleId = Role::query()->where('slug', RoleName::Admin->value)->value('id');

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@gyntraining.local'],
            [
                'name' => 'Administrador',
                'password' => Hash::make('password'),
                'phone' => null,
                'status' => UserStatus::Active,
            ],
        );

        if ($adminRoleId) {
            $admin->roles()->syncWithoutDetaching([$adminRoleId]);
        }
    }
}
