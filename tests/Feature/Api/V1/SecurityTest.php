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
use App\Models\WorkoutPlan;
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityTest extends TestCase
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

    public function test_student_cannot_view_other_student_workout_plan(): void
    {
        [$studentA, $planA] = $this->createStudentWithPlan('student-a@test.com');
        [$studentB] = $this->createStudentWithPlan('student-b@test.com');

        $this->actingAs($studentB, 'sanctum')
            ->getJson("/api/v1/workouts/{$planA->id}")
            ->assertForbidden();
    }

    public function test_student_cannot_access_other_student_progress(): void
    {
        [$studentAUser] = $this->createStudentWithPlan('progress-a@test.com');
        [$studentBUser] = $this->createStudentWithPlan('progress-b@test.com');

        $studentA = Student::query()->where('user_id', $studentAUser->id)->firstOrFail();

        $this->actingAs($studentBUser, 'sanctum')
            ->getJson('/api/v1/progress?student_id='.$studentA->id)
            ->assertForbidden();
    }

    public function test_student_cannot_create_gym(): void
    {
        [$studentUser] = $this->createStudentWithPlan('nogym@test.com');

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/gyms', [
                'name' => 'Academia Ilegal',
            ])
            ->assertForbidden();
    }

    public function test_trainer_cannot_update_other_trainers_workout_plan(): void
    {
        $gym = $this->createGym();
        [$trainerA, $student] = $this->createTrainer($gym, 'trainer-a@test.com');
        [$trainerB] = $this->createTrainer($gym, 'trainer-b@test.com');

        $plan = WorkoutPlan::query()->create([
            'student_id' => $student->id,
            'trainer_id' => $trainerA->trainer->id,
            'name' => 'Ficha do Trainer A',
            'status' => 'active',
        ]);

        $this->actingAs($trainerB, 'sanctum')
            ->putJson("/api/v1/workouts/{$plan->id}", [
                'name' => 'Tentativa de hijack',
            ])
            ->assertForbidden();
    }

    public function test_unauthenticated_cannot_access_protected_modules(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
        $this->getJson('/api/v1/progress')->assertUnauthorized();
        $this->getJson('/api/v1/history')->assertUnauthorized();
        $this->getJson('/api/v1/notifications')->assertUnauthorized();
        $this->getJson('/api/v1/workout-sessions')->assertUnauthorized();
    }

    public function test_student_cannot_delete_exercise(): void
    {
        [$studentUser] = $this->createStudentWithPlan('no-delete@test.com');

        $muscleGroup = MuscleGroup::query()->first();
        $category = ExerciseCategory::query()->first();

        $exercise = Exercise::query()->create([
            'name' => 'Exercicio Protegido',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'status' => 'active',
            'difficulty' => 'beginner',
        ]);

        $this->actingAs($studentUser, 'sanctum')
            ->deleteJson("/api/v1/exercises/{$exercise->id}")
            ->assertForbidden();
    }

    public function test_api_responses_never_expose_password(): void
    {
        $user = User::factory()->create([
            'email' => 'secret@test.com',
            'password' => 'password123',
        ]);
        $user->assignRole(RoleName::Student);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/v1/auth/me');

        $response->assertOk();
        $this->assertArrayNotHasKey('password', $response->json('data'));
    }

    private function createGym(): Gym
    {
        return Gym::query()->create([
            'name' => 'Gym Security',
            'slug' => 'gym-security-'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function createStudentWithPlan(string $email): array
    {
        $gym = $this->createGym();

        $trainerUser = User::factory()->create();
        $trainerUser->assignRole(RoleName::Trainer);
        $trainer = Trainer::query()->create([
            'user_id' => $trainerUser->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);

        $studentUser = User::factory()->create(['email' => $email]);
        $studentUser->assignRole(RoleName::Student);
        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);
        $studentUser->setRelation('student', $student);

        $plan = WorkoutPlan::query()->create([
            'student_id' => $student->id,
            'trainer_id' => $trainer->id,
            'name' => 'Ficha '.$email,
            'status' => 'active',
        ]);

        return [$studentUser, $plan];
    }

    private function createTrainer(Gym $gym, string $email): array
    {
        $trainerUser = User::factory()->create(['email' => $email]);
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

        return [$trainerUser, $student];
    }
}
