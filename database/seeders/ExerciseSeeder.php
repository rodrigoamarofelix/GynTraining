<?php

namespace Database\Seeders;

use App\Enums\ExerciseDifficulty;
use App\Enums\ExerciseStatus;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\MuscleGroup;
use Illuminate\Database\Seeder;

class ExerciseSeeder extends Seeder
{
    public function run(): void
    {
        $composto = ExerciseCategory::query()->where('slug', 'composto')->value('id');
        $isolado = ExerciseCategory::query()->where('slug', 'isolado')->value('id');
        $pesoLivre = ExerciseCategory::query()->where('slug', 'peso-livre')->value('id');
        $maquina = ExerciseCategory::query()->where('slug', 'maquina')->value('id');

        $groups = MuscleGroup::query()->pluck('id', 'slug');

        $exercises = [
            ['name' => 'Supino Reto', 'muscle_group' => 'peito', 'category' => $composto, 'equipment' => 'Barra', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Supino Inclinado', 'muscle_group' => 'peito', 'category' => $composto, 'equipment' => 'Barra', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Crucifixo', 'muscle_group' => 'peito', 'category' => $isolado, 'equipment' => 'Halteres', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Flexão de Braço', 'muscle_group' => 'peito', 'category' => $composto, 'equipment' => 'Peso corporal', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Puxada Frontal', 'muscle_group' => 'costas', 'category' => $maquina, 'equipment' => 'Polia', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Remada Curvada', 'muscle_group' => 'costas', 'category' => $composto, 'equipment' => 'Barra', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Remada Baixa', 'muscle_group' => 'costas', 'category' => $maquina, 'equipment' => 'Cabo', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Barra Fixa', 'muscle_group' => 'costas', 'category' => $composto, 'equipment' => 'Peso corporal', 'difficulty' => ExerciseDifficulty::Advanced],
            ['name' => 'Desenvolvimento', 'muscle_group' => 'ombros', 'category' => $pesoLivre, 'equipment' => 'Halteres', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Elevação Lateral', 'muscle_group' => 'ombros', 'category' => $isolado, 'equipment' => 'Halteres', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Elevação Frontal', 'muscle_group' => 'ombros', 'category' => $isolado, 'equipment' => 'Halteres', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Rosca Direta', 'muscle_group' => 'biceps', 'category' => $isolado, 'equipment' => 'Barra W', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Rosca Martelo', 'muscle_group' => 'biceps', 'category' => $isolado, 'equipment' => 'Halteres', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Rosca Concentrada', 'muscle_group' => 'biceps', 'category' => $isolado, 'equipment' => 'Halter', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Tríceps Pulley', 'muscle_group' => 'triceps', 'category' => $isolado, 'equipment' => 'Cabo', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Tríceps Francês', 'muscle_group' => 'triceps', 'category' => $isolado, 'equipment' => 'Halter', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Mergulho no Banco', 'muscle_group' => 'triceps', 'category' => $composto, 'equipment' => 'Peso corporal', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Agachamento Livre', 'muscle_group' => 'quadriceps', 'category' => $composto, 'equipment' => 'Barra', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Leg Press', 'muscle_group' => 'quadriceps', 'category' => $maquina, 'equipment' => 'Máquina', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Cadeira Extensora', 'muscle_group' => 'quadriceps', 'category' => $maquina, 'equipment' => 'Máquina', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Avanço', 'muscle_group' => 'quadriceps', 'category' => $composto, 'equipment' => 'Halteres', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Mesa Flexora', 'muscle_group' => 'posterior-coxa', 'category' => $maquina, 'equipment' => 'Máquina', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Stiff', 'muscle_group' => 'posterior-coxa', 'category' => $composto, 'equipment' => 'Barra', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Glúteo na Polia', 'muscle_group' => 'gluteos', 'category' => $isolado, 'equipment' => 'Cabo', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Elevação Pélvica', 'muscle_group' => 'gluteos', 'category' => $composto, 'equipment' => 'Barra', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Panturrilha em Pé', 'muscle_group' => 'panturrilha', 'category' => $isolado, 'equipment' => 'Máquina', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Panturrilha Sentado', 'muscle_group' => 'panturrilha', 'category' => $isolado, 'equipment' => 'Máquina', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Abdominal Crunch', 'muscle_group' => 'abdomen', 'category' => $isolado, 'equipment' => 'Peso corporal', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Prancha', 'muscle_group' => 'abdomen', 'category' => $isolado, 'equipment' => 'Peso corporal', 'difficulty' => ExerciseDifficulty::Beginner],
            ['name' => 'Abdominal Infra', 'muscle_group' => 'abdomen', 'category' => $isolado, 'equipment' => 'Banco', 'difficulty' => ExerciseDifficulty::Intermediate],
            ['name' => 'Levantamento Terra', 'muscle_group' => 'costas', 'category' => $composto, 'equipment' => 'Barra', 'difficulty' => ExerciseDifficulty::Advanced],
            ['name' => 'Hack Squat', 'muscle_group' => 'quadriceps', 'category' => $maquina, 'equipment' => 'Máquina', 'difficulty' => ExerciseDifficulty::Intermediate],
        ];

        foreach ($exercises as $exercise) {
            Exercise::query()->updateOrCreate(
                ['name' => $exercise['name']],
                [
                    'description' => 'Exercício para '.$exercise['name'],
                    'instructions' => 'Execute o movimento com controle e amplitude completa.',
                    'exercise_category_id' => $exercise['category'],
                    'muscle_group_id' => $groups[$exercise['muscle_group']],
                    'gym_id' => null,
                    'equipment' => $exercise['equipment'],
                    'difficulty' => $exercise['difficulty'],
                    'status' => ExerciseStatus::Active,
                ],
            );
        }
    }
}
