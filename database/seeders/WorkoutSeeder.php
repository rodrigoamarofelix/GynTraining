<?php

namespace Database\Seeders;

use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Enums\WorkoutPlanStatus;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class WorkoutSeeder extends Seeder
{
    public function run(): void
    {
        $gym = Gym::query()->where('slug', 'academia-central')->first();

        if (! $gym) {
            return;
        }

        $trainerUser = User::query()->updateOrCreate(
            ['email' => 'trainer@gyntraining.local'],
            [
                'name' => 'Carlos Personal',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
            ],
        );
        $trainerUser->assignRole(RoleName::Trainer);

        $trainer = Trainer::query()->updateOrCreate(
            ['user_id' => $trainerUser->id],
            [
                'gym_id' => $gym->id,
                'specialty' => 'Hipertrofia',
                'status' => 'active',
            ],
        );

        $studentUser = User::query()->updateOrCreate(
            ['email' => 'student@gyntraining.local'],
            [
                'name' => 'Maria Aluna',
                'password' => Hash::make('password'),
                'status' => UserStatus::Active,
            ],
        );
        $studentUser->assignRole(RoleName::Student);

        $student = Student::query()->updateOrCreate(
            ['user_id' => $studentUser->id],
            [
                'gym_id' => $gym->id,
                'trainer_id' => $trainer->id,
                'status' => 'active',
            ],
        );

        $exercises = Exercise::query()->whereIn('name', [
            'Supino Reto', 'Tríceps Pulley', 'Puxada Frontal', 'Rosca Direta', 'Agachamento Livre', 'Leg Press',
        ])->get()->keyBy('name');

        $plan = WorkoutPlan::query()->updateOrCreate(
            [
                'student_id' => $student->id,
                'trainer_id' => $trainer->id,
                'name' => 'Ficha ABC',
            ],
            [
                'description' => 'Treino dividido em peito/tríceps, costas/bíceps e pernas',
                'status' => WorkoutPlanStatus::Active,
            ],
        );

        if ($plan->days()->exists()) {
            return;
        }

        $dayA = $plan->days()->create(['name' => 'Treino A', 'description' => 'Peito + Tríceps', 'order' => 1]);
        $dayB = $plan->days()->create(['name' => 'Treino B', 'description' => 'Costas + Bíceps', 'order' => 2]);
        $dayC = $plan->days()->create(['name' => 'Treino C', 'description' => 'Pernas', 'order' => 3]);

        $this->seedDay($dayA, $exercises->get('Supino Reto'), [[12, 20], [10, 25], [8, 30]]);
        $this->seedDay($dayA, $exercises->get('Tríceps Pulley'), [[12, 15], [10, 20], [10, 20]], 2);

        $this->seedDay($dayB, $exercises->get('Puxada Frontal'), [[12, 40], [10, 45], [8, 50]]);
        $this->seedDay($dayB, $exercises->get('Rosca Direta'), [[12, 10], [10, 12], [8, 14]], 2);

        $this->seedDay($dayC, $exercises->get('Agachamento Livre'), [[10, 60], [8, 70], [6, 80]]);
        $this->seedDay($dayC, $exercises->get('Leg Press'), [[12, 100], [10, 120], [8, 140]], 2);
    }

    private function seedDay($day, ?Exercise $exercise, array $sets, int $order = 1): void
    {
        if (! $exercise) {
            return;
        }

        $workoutExercise = $day->exercises()->create([
            'exercise_id' => $exercise->id,
            'order' => $order,
            'rest_time' => 60,
        ]);

        foreach ($sets as $index => [$reps, $load]) {
            $workoutExercise->sets()->create([
                'set_number' => $index + 1,
                'repetitions' => $reps,
                'load' => $load,
                'rest_time' => 60,
            ]);
        }
    }
}
