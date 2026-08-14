<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProfileStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\Gym;
use App\Models\MuscleGroup;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Notifications\PendingStudentRegistrationNotification;
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogTest extends TestCase
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

    public function test_admin_can_create_gym(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/gyms', [
            'name' => 'Academia Fit',
            'description' => 'Academia de teste',
            'address' => 'Rua A, 123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Academia Fit');

        $this->assertDatabaseHas('gyms', ['name' => 'Academia Fit']);
    }

    public function test_admin_can_create_student_for_gym(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::query()->create([
            'name' => 'Gym Test',
            'slug' => 'gym-test',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/students', [
            'name' => 'Maria Aluna',
            'email' => 'maria@test.com',
            'password' => 'password123',
            'gym_id' => $gym->id,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'maria@test.com');

        $this->assertDatabaseHas('students', ['gym_id' => $gym->id]);
    }

    public function test_admin_can_create_trainer_for_gym(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::query()->create([
            'name' => 'Gym Trainer',
            'slug' => 'gym-trainer',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/trainers', [
            'name' => 'Carlos Professor',
            'email' => 'carlos@test.com',
            'password' => 'password123',
            'gym_id' => $gym->id,
            'specialty' => 'Hipertrofia',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.user.email', 'carlos@test.com');
    }

    public function test_admin_can_approve_pending_student_and_assign_trainer(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::query()->create([
            'name' => 'Gym Approval',
            'slug' => 'gym-approval',
            'status' => 'active',
        ]);

        $studentUser = User::factory()->create([
            'email' => 'pending-student@test.com',
            'status' => UserStatus::Inactive,
        ]);
        $studentUser->assignRole(RoleName::Student);

        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'gym_id' => $gym->id,
            'status' => ProfileStatus::Pending,
        ]);

        $trainerUser = User::factory()->create(['email' => 'trainer-approval@test.com']);
        $trainerUser->assignRole(RoleName::Trainer);

        $trainer = Trainer::query()->create([
            'user_id' => $trainerUser->id,
            'gym_id' => $gym->id,
            'status' => ProfileStatus::Active,
        ]);

        $admin->notify(new PendingStudentRegistrationNotification(
            studentName: $studentUser->name,
            studentEmail: $studentUser->email,
            gymName: $gym->name,
            studentId: $student->id,
            gymId: $gym->id,
        ));

        $this->assertSame(1, $admin->unreadNotifications()->count());

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/students/{$student->id}", [
            'status' => ProfileStatus::Active->value,
            'trainer_id' => $trainer->id,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', ProfileStatus::Active->value)
            ->assertJsonPath('data.trainer_id', $trainer->id);

        $student->refresh();
        $studentUser->refresh();

        $this->assertSame(ProfileStatus::Active, $student->status);
        $this->assertSame($trainer->id, $student->trainer_id);
        $this->assertSame(UserStatus::Active, $studentUser->status);
        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_admin_cannot_approve_pending_student_without_trainer(): void
    {
        $admin = $this->makeAdmin();
        $gym = Gym::query()->create([
            'name' => 'Gym Approval Error',
            'slug' => 'gym-approval-error',
            'status' => 'active',
        ]);

        $studentUser = User::factory()->create(['status' => UserStatus::Inactive]);
        $studentUser->assignRole(RoleName::Student);

        $student = Student::query()->create([
            'user_id' => $studentUser->id,
            'gym_id' => $gym->id,
            'status' => ProfileStatus::Pending,
        ]);

        $response = $this->actingAs($admin, 'sanctum')->putJson("/api/v1/students/{$student->id}", [
            'status' => ProfileStatus::Active->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonPath('errors.trainer_id.0', 'Selecione o professor responsável.');
    }

    public function test_admin_can_create_exercise(): void
    {
        $admin = $this->makeAdmin();
        $muscleGroup = MuscleGroup::query()->first();
        $category = ExerciseCategory::query()->first();

        $response = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/exercises', [
            'name' => 'Supino Teste',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'equipment' => 'Barra',
            'difficulty' => 'intermediate',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Supino Teste');

        $this->assertDatabaseHas('exercises', ['name' => 'Supino Teste']);
    }

    public function test_authenticated_user_can_list_exercises(): void
    {
        $admin = $this->makeAdmin();
        $muscleGroup = MuscleGroup::query()->first();
        $category = ExerciseCategory::query()->first();

        Exercise::query()->create([
            'name' => 'Agachamento Teste',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'equipment' => 'Barra',
            'difficulty' => 'intermediate',
            'status' => 'active',
        ]);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/exercises');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_unauthenticated_user_cannot_access_catalog_routes(): void
    {
        $this->getJson('/api/v1/exercises')->assertUnauthorized();
        $this->getJson('/api/v1/gyms')->assertUnauthorized();
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        return $admin;
    }
}
