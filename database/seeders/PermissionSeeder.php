<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            ['name' => 'Gerenciar usuários', 'slug' => 'users.manage'],
            ['name' => 'Gerenciar academias', 'slug' => 'gyms.manage'],
            ['name' => 'Gerenciar exercícios', 'slug' => 'exercises.manage'],
            ['name' => 'Gerenciar treinos', 'slug' => 'workouts.manage'],
            ['name' => 'Executar treinos', 'slug' => 'workouts.execute'],
            ['name' => 'Visualizar evolução', 'slug' => 'progress.view'],
        ];

        foreach ($permissions as $permission) {
            Permission::query()->updateOrCreate(['slug' => $permission['slug']], $permission);
        }

        $rolePermissions = [
            'admin' => Permission::query()->pluck('id')->all(),
            'gym_admin' => Permission::query()->whereIn('slug', [
                'users.manage', 'gyms.manage', 'exercises.manage', 'workouts.manage', 'progress.view',
            ])->pluck('id')->all(),
            'trainer' => Permission::query()->whereIn('slug', [
                'workouts.manage', 'progress.view',
            ])->pluck('id')->all(),
            'student' => Permission::query()->whereIn('slug', [
                'workouts.execute', 'progress.view',
            ])->pluck('id')->all(),
        ];

        foreach ($rolePermissions as $roleSlug => $permissionIds) {
            $role = Role::query()->where('slug', $roleSlug)->first();

            if ($role) {
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
