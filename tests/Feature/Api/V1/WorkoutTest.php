<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleName;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\Gym;
use App\Models\MuscleGroup;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutExercise;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSet;
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WorkoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            MuscleGroupSeeder::class,
            ExerciseCategorySeeder::class,
        ]);
    }

    public function test_trainer_can_create_workout_plan_with_days_and_sets(): void
    {
        [$trainerUser, $student, $exercise] = $this->createTrainerStudentAndExercise();

        $response = $this->actingAs($trainerUser, 'sanctum')->postJson('/api/v1/workouts', [
            'student_id' => $student->id,
            'name' => 'Ficha A/B/C',
            'description' => 'Treino de hipertrofia',
            'days' => [
                [
                    'name' => 'Treino A',
                    'order' => 1,
                    'exercises' => [
                        [
                            'exercise_id' => $exercise->id,
                            'order' => 1,
                            'rest_time' => 60,
                            'sets' => [
                                ['set_number' => 1, 'repetitions' => 12, 'load' => 20],
                                ['set_number' => 2, 'repetitions' => 10, 'load' => 25],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Ficha A/B/C')
            ->assertJsonPath('data.days.0.name', 'Treino A');

        $this->assertDatabaseHas('workout_plans', ['name' => 'Ficha A/B/C']);
        $this->assertDatabaseHas('workout_days', ['name' => 'Treino A']);
        $this->assertDatabaseHas('workout_sets', ['set_number' => 1, 'repetitions' => 12]);
    }

    public function test_delete_workout_plan_uses_soft_delete_cascade(): void
    {
        [$trainerUser, $student, $exercise] = $this->createTrainerStudentAndExercise();

        $plan = WorkoutPlan::query()->create([
            'student_id' => $student->id,
            'trainer_id' => $trainerUser->trainer->id,
            'name' => 'Ficha Temp',
            'status' => 'active',
        ]);

        $day = WorkoutDay::query()->create([
            'workout_plan_id' => $plan->id,
            'name' => 'Dia 1',
            'order' => 1,
        ]);

        $workoutExercise = WorkoutExercise::query()->create([
            'workout_day_id' => $day->id,
            'exercise_id' => $exercise->id,
            'order' => 1,
        ]);

        $set = WorkoutSet::query()->create([
            'workout_exercise_id' => $workoutExercise->id,
            'set_number' => 1,
            'repetitions' => 10,
            'load' => 20,
        ]);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/workouts/{$plan->id}")
            ->assertOk();

        $this->assertSoftDeleted('workout_plans', ['id' => $plan->id]);
        $this->assertSoftDeleted('workout_days', ['id' => $day->id]);
        $this->assertSoftDeleted('workout_exercises', ['id' => $workoutExercise->id]);
        $this->assertSoftDeleted('workout_sets', ['id' => $set->id]);

        $this->assertDatabaseHas('workout_plans', ['id' => $plan->id]);
        $this->assertDatabaseHas('workout_sets', ['id' => $set->id]);
    }

    public function test_delete_exercise_uses_soft_delete(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $muscleGroup = MuscleGroup::query()->first();
        $category = ExerciseCategory::query()->first();

        $exercise = Exercise::query()->create([
            'name' => 'Exercicio Soft Delete',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'status' => 'active',
            'difficulty' => 'beginner',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertOk();

        $this->assertSoftDeleted('exercises', ['id' => $exercise->id]);
        $this->assertDatabaseHas('exercises', ['id' => $exercise->id]);
    }

    public function test_student_can_view_own_workout_plan(): void
    {
        [$trainerUser, $student] = $this->createTrainerStudentAndExercise();

        $plan = WorkoutPlan::query()->create([
            'student_id' => $student->id,
            'trainer_id' => $trainerUser->trainer->id,
            'name' => 'Minha Ficha',
            'status' => 'active',
        ]);

        $studentUser = $student->user;

        $this->actingAs($studentUser, 'sanctum')
            ->getJson("/api/v1/workouts/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Minha Ficha');
    }

    private function createTrainerStudentAndExercise(): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Workout',
            'slug' => 'gym-workout',
            'status' => 'active',
        ]);

        $trainerUser = User::factory()->create();
        $trainerUser->assignRole(RoleName::Trainer);
        $trainer = Trainer::query()->create([
            'user_id' => $trainerUser->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);
        $trainerUser->setRelation('trainer', $trainer);

        $studentUser = User::factory()->create();
        $studentUser->assignRole(RoleName::Student);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);
        $student->setRelation('user', $studentUser);

        $muscleGroup = MuscleGroup::query()->first();
        $category = ExerciseCategory::query()->first();

        $exercise = Exercise::query()->create([
            'name' => 'Supino Teste Workout',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'status' => 'active',
            'difficulty' => 'intermediate',
        ]);

        return [$trainerUser, $student, $exercise];
    }
}
