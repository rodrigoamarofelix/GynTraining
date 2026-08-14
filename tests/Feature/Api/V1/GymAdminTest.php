<?php

namespace Tests\Feature\Api\V1;

use App\Enums\GymStatus;
use App\Enums\RoleName;
use App\Models\Exercise;
use App\Models\Gym;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymAdminTest extends TestCase
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

    public function test_gym_admin_only_sees_managed_gyms(): void
    {
        $managedGym = $this->createGym('Academia Central', 'academia-central');
        $otherGym = $this->createGym('Academia Norte', 'academia-norte');
        $gymAdmin = $this->createGymAdmin($managedGym);

        $response = $this->actingAs($gymAdmin, 'sanctum')->getJson('/api/v1/gyms');

        $ids = collect($response->json('data'))->pluck('id');

        $response->assertOk();
        $this->assertTrue($ids->contains($managedGym->id));
        $this->assertFalse($ids->contains($otherGym->id));
    }

    public function test_gym_admin_cannot_create_gym(): void
    {
        $gym = $this->createGym('Academia Central', 'academia-central');
        $gymAdmin = $this->createGymAdmin($gym);

        $this->actingAs($gymAdmin, 'sanctum')->postJson('/api/v1/gyms', [
            'name' => 'Nova Academia',
            'description' => 'Teste',
        ])->assertForbidden();
    }

    public function test_gym_admin_can_update_managed_gym(): void
    {
        $gym = $this->createGym('Academia Central', 'academia-central');
        $gymAdmin = $this->createGymAdmin($gym);

        $this->actingAs($gymAdmin, 'sanctum')->putJson("/api/v1/gyms/{$gym->id}", [
            'name' => 'Academia Central Atualizada',
        ])->assertOk()
            ->assertJsonPath('data.name', 'Academia Central Atualizada');
    }

    public function test_gym_admin_cannot_update_unmanaged_gym(): void
    {
        $managedGym = $this->createGym('Academia Central', 'academia-central');
        $otherGym = $this->createGym('Academia Norte', 'academia-norte');
        $gymAdmin = $this->createGymAdmin($managedGym);

        $this->actingAs($gymAdmin, 'sanctum')->putJson("/api/v1/gyms/{$otherGym->id}", [
            'name' => 'Tentativa',
        ])->assertForbidden();
    }

    public function test_gym_admin_students_list_is_scoped_to_managed_gym(): void
    {
        $managedGym = $this->createGym('Academia Central', 'academia-central');
        $otherGym = $this->createGym('Academia Norte', 'academia-norte');
        $gymAdmin = $this->createGymAdmin($managedGym);

        $managedStudent = $this->createStudentForGym($managedGym, 'aluno1@test.com');
        $this->createStudentForGym($otherGym, 'aluno2@test.com');

        $response = $this->actingAs($gymAdmin, 'sanctum')->getJson('/api/v1/students');

        $ids = collect($response->json('data'))->pluck('id');

        $response->assertOk();
        $this->assertTrue($ids->contains($managedStudent->id));
        $this->assertCount(1, $ids);
    }

    public function test_gym_admin_cannot_create_student_for_unmanaged_gym(): void
    {
        $managedGym = $this->createGym('Academia Central', 'academia-central');
        $otherGym = $this->createGym('Academia Norte', 'academia-norte');
        $gymAdmin = $this->createGymAdmin($managedGym);

        $this->actingAs($gymAdmin, 'sanctum')->postJson('/api/v1/students', [
            'name' => 'Aluno Externo',
            'email' => 'externo@test.com',
            'password' => 'password123',
            'gym_id' => $otherGym->id,
        ])->assertUnprocessable()
            ->assertJsonPath('errors.gym_id.0', 'Você não tem permissão para esta academia.');
    }

    public function test_gym_admin_exercises_list_includes_global_and_managed_gym(): void
    {
        $managedGym = $this->createGym('Academia Central', 'academia-central');
        $otherGym = $this->createGym('Academia Norte', 'academia-norte');
        $gymAdmin = $this->createGymAdmin($managedGym);

        $globalExercise = $this->createExercise(null, 'Global');
        $managedExercise = $this->createExercise($managedGym->id, 'Da Academia');
        $this->createExercise($otherGym->id, 'De Outra');

        $response = $this->actingAs($gymAdmin, 'sanctum')->getJson('/api/v1/exercises');

        $names = collect($response->json('data'))->pluck('name');

        $response->assertOk();
        $this->assertTrue($names->contains('Global'));
        $this->assertTrue($names->contains('Da Academia'));
        $this->assertFalse($names->contains('De Outra'));
    }

    public function test_gym_admin_dashboard_is_scoped(): void
    {
        $managedGym = $this->createGym('Academia Central', 'academia-central');
        $this->createGym('Academia Norte', 'academia-norte');
        $gymAdmin = $this->createGymAdmin($managedGym);

        $this->createStudentForGym($managedGym, 'aluno1@test.com');

        $response = $this->actingAs($gymAdmin, 'sanctum')->getJson('/api/v1/dashboard');

        $response->assertOk()
            ->assertJsonPath('data.role', RoleName::GymAdmin->value)
            ->assertJsonPath('data.stats.total_gyms', 1)
            ->assertJsonPath('data.stats.total_students', 1);
    }

    private function createGym(string $name, string $slug): Gym
    {
        return Gym::query()->create([
            'name' => $name,
            'slug' => $slug,
            'status' => GymStatus::Active,
        ]);
    }

    private function createGymAdmin(Gym $gym): User
    {
        $user = User::factory()->create();
        $user->assignRole(RoleName::GymAdmin);
        $user->gyms()->attach($gym->id);

        return $user;
    }

    private function createStudentForGym(Gym $gym, string $email): Student
    {
        $user = User::factory()->create(['email' => $email]);
        $user->assignRole(RoleName::Student);

        return Student::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);
    }

    private function createExercise(?int $gymId, string $name): Exercise
    {
        return Exercise::query()->create([
            'name' => $name,
            'exercise_category_id' => 1,
            'muscle_group_id' => 1,
            'gym_id' => $gymId,
            'equipment' => 'Barra',
            'difficulty' => 'intermediate',
            'status' => 'active',
        ]);
    }
}
