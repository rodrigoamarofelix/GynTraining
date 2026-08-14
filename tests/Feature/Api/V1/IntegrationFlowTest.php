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
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntegrationFlowTest extends TestCase
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

    public function test_complete_training_lifecycle(): void
    {
        [$trainerUser, $studentUser, $exercise] = $this->bootstrapTrainerAndStudent();

        $planResponse = $this->actingAs($trainerUser, 'sanctum')->postJson('/api/v1/workouts', [
            'student_id' => $studentUser->student->id,
            'name' => 'Ficha Integração',
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
                                ['set_number' => 1, 'repetitions' => 12, 'load' => 20],
                                ['set_number' => 2, 'repetitions' => 10, 'load' => 25],
                            ],
                        ],
                    ],
                ],
            ],
        ])->assertCreated();

        $planId = $planResponse->json('data.id');
        $dayId = $planResponse->json('data.days.0.id');

        $startResponse = $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$planId}/start", [
                'workout_day_id' => $dayId,
            ])
            ->assertCreated();

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
            ->assertJsonPath('data.rest_time', 90);

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/workout-sessions', [
                'workout_session_id' => $sessionId,
                'exercise_id' => $exercise->id,
                'set_number' => 2,
                'repetitions' => 10,
                'load' => 25,
            ])
            ->assertCreated();

        $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/workouts/{$planId}/finish")
            ->assertOk()
            ->assertJsonPath('data.status', 'completed');

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/body-measurements', [
                'student_id' => $studentUser->student->id,
                'measured_at' => now()->toDateString(),
                'weight' => 75,
                'height' => 175,
            ])
            ->assertCreated()
            ->assertJsonPath('data.bmi', 24.49);

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/history')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/progress')
            ->assertOk()
            ->assertJsonPath('data.workout_count', 1)
            ->assertJsonPath('data.max_load', 25);

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'student')
            ->assertJsonPath('data.stats.workouts_this_month', 1);

        $this->actingAs($trainerUser, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'trainer')
            ->assertJsonPath('data.stats.total_students', 1);
    }

    public function test_register_requires_approval_before_login(): void
    {
        $gym = Gym::query()->create([
            'name' => 'Academia Fluxo',
            'slug' => 'academia-fluxo',
            'status' => 'active',
        ]);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Fluxo Completo',
            'email' => 'fluxo@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gym_id' => $gym->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.pending_approval', true)
            ->assertJsonPath('data.token', null);

        $this->postJson('/api/v1/auth/login', [
            'email' => 'fluxo@test.com',
            'password' => 'password123',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_register_login_after_admin_approval(): void
    {
        $gym = Gym::query()->create([
            'name' => 'Academia Fluxo',
            'slug' => 'academia-fluxo-2',
            'status' => 'active',
        ]);

        $trainerUser = User::factory()->create();
        $trainerUser->assignRole(RoleName::Trainer);
        $trainer = Trainer::query()->create([
            'user_id' => $trainerUser->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);
        $adminToken = $admin->createToken('test')->plainTextToken;

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Fluxo Completo',
            'email' => 'fluxo-aprovado@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'gym_id' => $gym->id,
        ])->assertCreated();

        $student = Student::query()->whereHas('user', fn ($query) => $query->where('email', 'fluxo-aprovado@test.com'))->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$adminToken)
            ->putJson("/api/v1/students/{$student->id}", [
                'status' => 'active',
                'trainer_id' => $trainer->id,
            ])
            ->assertOk();

        $this->withHeaders([])
            ->postJson('/api/v1/auth/login', [
                'email' => 'fluxo-aprovado@test.com',
                'password' => 'password123',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.email', 'fluxo-aprovado@test.com')
            ->assertJsonStructure(['data' => ['token', 'user']]);
    }

    private function bootstrapTrainerAndStudent(): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Integration',
            'slug' => 'gym-integration',
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
            'name' => 'Supino Integração',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'status' => 'active',
            'difficulty' => 'intermediate',
        ]);

        return [$trainerUser, $studentUser, $exercise];
    }
}
