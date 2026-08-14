<?php

namespace Database\Seeders;

use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;

class MuscleGroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Peito', 'slug' => 'peito', 'description' => 'Músculos do tórax'],
            ['name' => 'Costas', 'slug' => 'costas', 'description' => 'Dorsais e região superior posterior'],
            ['name' => 'Ombros', 'slug' => 'ombros', 'description' => 'Deltoides'],
            ['name' => 'Bíceps', 'slug' => 'biceps', 'description' => 'Flexores do cotovelo'],
            ['name' => 'Tríceps', 'slug' => 'triceps', 'description' => 'Extensores do cotovelo'],
            ['name' => 'Quadríceps', 'slug' => 'quadriceps', 'description' => 'Parte anterior da coxa'],
            ['name' => 'Posterior de Coxa', 'slug' => 'posterior-coxa', 'description' => 'Isquiotibiais'],
            ['name' => 'Glúteos', 'slug' => 'gluteos', 'description' => 'Região glútea'],
            ['name' => 'Panturrilha', 'slug' => 'panturrilha', 'description' => 'Gastrocnêmio e sóleo'],
            ['name' => 'Abdômen', 'slug' => 'abdomen', 'description' => 'Core e região abdominal'],
        ];

        foreach ($groups as $group) {
            MuscleGroup::query()->updateOrCreate(['slug' => $group['slug']], $group);
        }
    }
}
