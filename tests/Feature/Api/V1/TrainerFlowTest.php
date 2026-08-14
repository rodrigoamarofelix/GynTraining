<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleName;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutPlan;
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerFlowTest extends TestCase
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

    public function test_trainer_lists_assigned_students_and_students_with_plans(): void
    {
        [$trainerUser, $trainer, $assignedStudent, $planStudent] = $this->createTrainerContext();

        $this->actingAs($trainerUser, 'sanctum')
            ->getJson('/api/v1/students')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_trainer_cannot_create_plan_for_student_of_another_trainer(): void
    {
        [$trainerUser, $trainer, $assignedStudent] = $this->createTrainerContext();
        $otherTrainer = Trainer::query()->create([
            'user_id' => User::factory()->create()->id,
            'gym_id' => $trainer->gym_id,
            'status' => 'active',
        ]);

        $foreignStudent = $this->createStudent($trainer->gym_id, 'foreign@test.com', $otherTrainer->id);
        $exercise = $this->createExercise();

        $this->actingAs($trainerUser, 'sanctum')->postJson('/api/v1/workouts', [
            'student_id' => $foreignStudent->id,
            'name' => 'Ficha inválida',
            'days' => [
                [
                    'name' => 'Treino A',
                    'order' => 1,
                    'exercises' => [
                        [
                            'exercise_id' => $exercise->id,
                            'order' => 1,
                            'sets' => [
                                ['set_number' => 1, 'repetitions' => 10, 'load' => 20],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertUnprocessable()
            ->assertJsonPath('errors.student_id.0', 'Este aluno já está vinculado a outro professor.');
    }

    public function test_creating_plan_assigns_trainer_to_unassigned_student(): void
    {
        [$trainerUser, $trainer] = $this->createTrainerContext();
        $student = $this->createStudent($trainer->gym_id, 'novo@test.com');
        $exercise = $this->createExercise();

        $this->actingAs($trainerUser, 'sanctum')->postJson('/api/v1/workouts', [
            'student_id' => $student->id,
            'name' => 'Ficha inicial',
            'days' => [
                [
                    'name' => 'Treino A',
                    'order' => 1,
                    'exercises' => [
                        [
                            'exercise_id' => $exercise->id,
                            'order' => 1,
                            'sets' => [
                                ['set_number' => 1, 'repetitions' => 12, 'load' => 30],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertCreated();

        $this->assertDatabaseHas('students', [
            'id' => $student->id,
            'trainer_id' => $trainer->id,
        ]);
    }

    public function test_trainer_can_update_plan_structure_with_days(): void
    {
        [$trainerUser, $trainer, $assignedStudent] = $this->createTrainerContext();
        $exercise = $this->createExercise();

        $plan = WorkoutPlan::query()->create([
            'student_id' => $assignedStudent->id,
            'trainer_id' => $trainer->id,
            'name' => 'Ficha original',
            'status' => 'active',
        ]);

        $this->actingAs($trainerUser, 'sanctum')->putJson("/api/v1/workouts/{$plan->id}", [
            'name' => 'Ficha atualizada',
            'days' => [
                [
                    'name' => 'Treino A',
                    'order' => 1,
                    'exercises' => [
                        [
                            'exercise_id' => $exercise->id,
                            'order' => 1,
                            'rest_time' => 90,
                            'sets' => [
                                ['set_number' => 1, 'repetitions' => 8, 'load' => 40],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertOk()
            ->assertJsonPath('data.name', 'Ficha atualizada');

        $this->assertDatabaseHas('workout_days', [
            'workout_plan_id' => $plan->id,
            'name' => 'Treino A',
        ]);
    }

    public function test_trainer_can_restore_soft_deleted_workout_plan(): void
    {
        [$trainerUser, $trainer, $assignedStudent] = $this->createTrainerContext();

        $plan = WorkoutPlan::query()->create([
            'student_id' => $assignedStudent->id,
            'trainer_id' => $trainer->id,
            'name' => 'Ficha excluída',
            'status' => 'active',
        ]);

        $this->actingAs($trainerUser, 'sanctum')
            ->deleteJson("/api/v1/workouts/{$plan->id}")
            ->assertOk();

        $this->assertSoftDeleted('workout_plans', ['id' => $plan->id]);

        $this->actingAs($trainerUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.name', 'Ficha excluída');

        $this->assertNull(WorkoutPlan::query()->find($plan->id)?->deleted_at);
    }

    public function test_trainer_can_view_soft_deleted_workout_plan(): void
    {
        [$trainerUser, $trainer, $assignedStudent] = $this->createTrainerContext();

        $plan = WorkoutPlan::query()->create([
            'student_id' => $assignedStudent->id,
            'trainer_id' => $trainer->id,
            'name' => 'Ficha arquivada',
            'status' => 'active',
        ]);

        $plan->delete();

        $this->actingAs($trainerUser, 'sanctum')
            ->getJson("/api/v1/workouts/{$plan->id}")
            ->assertOk()
            ->assertJsonPath('data.name', 'Ficha arquivada')
            ->assertJsonPath('data.deleted_at', fn ($value) => $value !== null);
    }

    private function createTrainerContext(): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Trainer Flow',
            'slug' => 'gym-trainer-flow',
            'status' => 'active',
        ]);

        $trainerUser = User::factory()->create();
        $trainerUser->assignRole(RoleName::Trainer);

        $trainer = Trainer::query()->create([
            'user_id' => $trainerUser->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);

        $assignedStudent = $this->createStudent($gym->id, 'assigned@test.com', $trainer->id);

        $planStudent = $this->createStudent($gym->id, 'plan@test.com');
        WorkoutPlan::query()->create([
            'student_id' => $planStudent->id,
            'trainer_id' => $trainer->id,
            'name' => 'Ficha existente',
            'status' => 'active',
        ]);

        return [$trainerUser, $trainer, $assignedStudent, $planStudent];
    }

    private function createStudent(int $gymId, string $email, ?int $trainerId = null): Student
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole(RoleName::Student);

        return Student::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gymId,
            'trainer_id' => $trainerId,
            'status' => 'active',
        ]);
    }

    private function createExercise(): Exercise
    {
        return Exercise::query()->create([
            'name' => 'Exercício Teste',
            'exercise_category_id' => 1,
            'muscle_group_id' => 1,
            'equipment' => 'Barra',
            'difficulty' => 'intermediate',
            'status' => 'active',
        ]);
    }
}
