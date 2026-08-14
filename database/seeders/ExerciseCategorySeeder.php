<?php

namespace Database\Seeders;

use App\Models\ExerciseCategory;
use Illuminate\Database\Seeder;

class ExerciseCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Composto', 'slug' => 'composto', 'description' => 'Exercícios multiarticulares'],
            ['name' => 'Isolado', 'slug' => 'isolado', 'description' => 'Exercícios monoarticulares'],
            ['name' => 'Peso Livre', 'slug' => 'peso-livre', 'description' => 'Halteres, barras e kettlebells'],
            ['name' => 'Máquina', 'slug' => 'maquina', 'description' => 'Exercícios em aparelhos'],
            ['name' => 'Calistenia', 'slug' => 'calistenia', 'description' => 'Peso corporal'],
        ];

        foreach ($categories as $category) {
            ExerciseCategory::query()->updateOrCreate(['slug' => $category['slug']], $category);
        }
    }
}
