<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['name' => 'Administrador', 'slug' => RoleName::Admin->value, 'description' => 'Acesso total ao sistema'],
            ['name' => 'Admin da Academia', 'slug' => RoleName::GymAdmin->value, 'description' => 'Gerencia academia e usuários'],
            ['name' => 'Professor', 'slug' => RoleName::Trainer->value, 'description' => 'Gerencia treinos de alunos'],
            ['name' => 'Aluno', 'slug' => RoleName::Student->value, 'description' => 'Executa e acompanha treinos'],
        ];

        foreach ($roles as $role) {
            Role::query()->updateOrCreate(['slug' => $role['slug']], $role);
        }
    }
}
