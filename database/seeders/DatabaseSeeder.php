<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            PermissionSeeder::class,
            AdminSeeder::class,
            GymAdminSeeder::class,
            MuscleGroupSeeder::class,
            ExerciseCategorySeeder::class,
            GymSeeder::class,
            ExerciseSeeder::class,
            WorkoutSeeder::class,
        ]);
    }
}
