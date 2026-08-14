<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Gym;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GymAdminSeeder extends Seeder
{
    public function run(): void
    {
        $gymAdminRoleId = Role::query()->where('slug', RoleName::GymAdmin->value)->value('id');
        $gym = Gym::query()->where('slug', 'academia-central')->first();

        $gymAdmin = User::query()->updateOrCreate(
            ['email' => 'gymadmin@gyntraining.local'],
            [
                'name' => 'Admin Academia Central',
                'password' => Hash::make('password'),
                'phone' => null,
                'status' => UserStatus::Active,
            ],
        );

        if ($gymAdminRoleId) {
            $gymAdmin->roles()->syncWithoutDetaching([$gymAdminRoleId]);
        }

        if ($gym) {
            $gymAdmin->gyms()->syncWithoutDetaching([$gym->id]);
        }
    }
}
