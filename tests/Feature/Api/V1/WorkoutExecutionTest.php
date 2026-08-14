<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleName;
use App\Enums\WorkoutSessionStatus;
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

class WorkoutExecutionTest extends TestCase
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

    public function test_student_can_start_log_sets_finish_and_view_history(): void
    {
        [$studentUser, $plan, $day, $exercise] = $this->createStudentWorkoutContext();

        $startResponse = $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/start", [
                'workout_day_id' => $day->id,
            ]);

        $startResponse->assertCreated()
            ->assertJsonPath('data.status', WorkoutSessionStatus::InProgress->value)
            ->assertJsonPath('data.workout_day_id', $day->id);

        $sessionId = $startResponse->json('data.id');

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/workout-sessions', [
                'workout_session_id' => $sessionId,
                'exercise_id' => $exercise->id,
                'set_number' => 1,
                'repetitions' => 12,
                'load' => 20,
            ])
            ->assertCreated()
            ->assertJsonPath('data.set_number', 1)
            ->assertJsonPath('data.repetitions', 12)
            ->assertJsonPath('data.rest_time', 60);

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/workout-sessions', [
                'workout_session_id' => $sessionId,
                'exercise_id' => $exercise->id,
                'set_number' => 2,
                'repetitions' => 10,
                'load' => 25,
            ])
            ->assertCreated();

        $finishResponse = $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/finish", [
                'notes' => 'Treino concluído',
            ]);

        $finishResponse->assertOk()
            ->assertJsonPath('data.status', WorkoutSessionStatus::Completed->value)
            ->assertJsonPath('data.notes', 'Treino concluído');

        $this->assertIsInt($finishResponse->json('data.duration_seconds'));
        $this->assertNotNull($finishResponse->json('data.duration_seconds'));

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/history')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.exercise_id', $exercise->id);
    }

    public function test_student_cannot_start_second_session_while_one_is_active(): void
    {
        [$studentUser, $plan, $day] = $this->createStudentWorkoutContext();

        $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/start", [
                'workout_day_id' => $day->id,
            ])
            ->assertCreated();

        $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/start", [
                'workout_day_id' => $day->id,
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.session.0', 'Já existe um treino em andamento. Finalize-o antes de iniciar outro.');
    }

    public function test_cannot_log_set_on_finished_session(): void
    {
        [$studentUser, $plan, $day, $exercise] = $this->createStudentWorkoutContext();

        $sessionId = $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/start", [
                'workout_day_id' => $day->id,
            ])
            ->json('data.id');

        $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/finish")
            ->assertOk();

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/workout-sessions', [
                'workout_session_id' => $sessionId,
                'exercise_id' => $exercise->id,
                'set_number' => 1,
                'repetitions' => 12,
                'load' => 20,
            ])
            ->assertStatus(422)
            ->assertJsonPath('errors.session.0', 'Não é possível registrar séries em um treino finalizado.');
    }

    public function test_trainer_can_view_student_session(): void
    {
        [$studentUser, $plan, $day, , $trainerUser] = $this->createStudentWorkoutContext(withTrainer: true);

        $sessionId = $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$plan->id}/start", [
                'workout_day_id' => $day->id,
            ])
            ->json('data.id');

        $this->actingAs($trainerUser, 'sanctum')
            ->getJson("/api/v1/workout-sessions/{$sessionId}")
            ->assertOk()
            ->assertJsonPath('data.id', $sessionId);
    }

    private function createStudentWorkoutContext(bool $withTrainer = false): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Execution',
            'slug' => 'gym-execution',
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
        $studentUser->setRelation('student', $student);

        $muscleGroup = MuscleGroup::query()->first();
        $category = ExerciseCategory::query()->first();

        $exercise = Exercise::query()->create([
            'name' => 'Supino Execução',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'status' => 'active',
            'difficulty' => 'intermediate',
        ]);

        $plan = WorkoutPlan::query()->create([
            'student_id' => $student->id,
            'trainer_id' => $trainer->id,
            'name' => 'Ficha Execução',
            'status' => 'active',
        ]);

        $day = WorkoutDay::query()->create([
            'workout_plan_id' => $plan->id,
            'name' => 'Treino A',
            'order' => 1,
        ]);

        $workoutExercise = WorkoutExercise::query()->create([
            'workout_day_id' => $day->id,
            'exercise_id' => $exercise->id,
            'order' => 1,
            'rest_time' => 60,
        ]);

        WorkoutSet::query()->create([
            'workout_exercise_id' => $workoutExercise->id,
            'set_number' => 1,
            'repetitions' => 12,
            'load' => 20,
        ]);

        if ($withTrainer) {
            return [$studentUser, $plan, $day, $exercise, $trainerUser];
        }

        return [$studentUser, $plan, $day, $exercise];
    }
}
