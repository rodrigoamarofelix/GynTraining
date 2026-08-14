<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProfileStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Gym;
use App\Models\GymActivityLog;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GymActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
        ]);
    }

    public function test_admin_can_view_gym_activity_logs(): void
    {
        $admin = $this->makeAdmin();
        $gym = $this->createGym();

        GymActivityLog::query()->create([
            'gym_id' => $gym->id,
            'performed_by' => $admin->id,
            'action' => 'created',
            'changes' => [],
            'summary' => 'Administrador cadastrou a academia.',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/gyms/{$gym->id}/activity-logs")
            ->assertOk()
            ->assertJsonPath('data.0.action', 'created');
    }

    public function test_creating_gym_logs_activity(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/gyms', [
            'name' => 'Academia Fit',
            'address' => 'Rua A, 123',
        ])->assertCreated();

        $this->assertDatabaseHas('gym_activity_logs', [
            'action' => 'created',
            'performed_by' => $admin->id,
        ]);
    }

    public function test_deleting_gym_cascades_to_active_members_with_marker(): void
    {
        $admin = $this->makeAdmin();
        $gym = $this->createGym();
        $student = $this->createStudent($gym);
        $trainer = $this->createTrainer($gym);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/gyms/{$gym->id}")
            ->assertOk();

        $student->refresh();
        $trainer->refresh();

        $this->assertSoftDeleted('gyms', ['id' => $gym->id]);
        $this->assertSoftDeleted('students', ['id' => $student->id]);
        $this->assertSoftDeleted('trainers', ['id' => $trainer->id]);
        $this->assertNotNull($student->gym_cascade_at);
        $this->assertNotNull($trainer->gym_cascade_at);
    }

    public function test_gym_restore_does_not_restore_individually_deleted_student(): void
    {
        $admin = $this->makeAdmin();
        $gym = $this->createGym();
        $individualStudent = $this->createStudent($gym, 'individual@test.com');
        $cascadeStudent = $this->createStudent($gym, 'cascade@test.com');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/students/{$individualStudent->id}")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/gyms/{$gym->id}")
            ->assertOk();

        $individualStudent->refresh();
        $cascadeStudent->refresh();

        $this->assertNull($individualStudent->gym_cascade_at);
        $this->assertNotNull($cascadeStudent->gym_cascade_at);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/gyms/{$gym->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', 'active');

        $this->assertNotNull(Student::query()->find($cascadeStudent->id));
        $this->assertSoftDeleted('students', ['id' => $individualStudent->id]);
        $this->assertNull(Student::withTrashed()->find($individualStudent->id)?->gym_cascade_at);
    }

    public function test_gyms_list_defaults_to_active_scope(): void
    {
        $admin = $this->makeAdmin();
        $active = $this->createGym('active-gym', 'active');
        $inactive = $this->createGym('inactive-gym', 'inactive');

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/gyms');

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_admin_can_view_gym_members(): void
    {
        $admin = $this->makeAdmin();
        $gym = $this->createGym();
        $student = $this->createStudent($gym, 'member-student@test.com');
        $trainer = $this->createTrainer($gym, 'member-trainer@test.com');

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/gyms/{$gym->id}/members")
            ->assertOk()
            ->assertJsonPath('data.students.0.id', $student->id)
            ->assertJsonPath('data.trainers.0.id', $trainer->id)
            ->assertJsonPath('data.students.0.name', $student->user->name)
            ->assertJsonPath('data.trainers.0.email', $trainer->user->email);
    }

    public function test_gym_members_inactive_scope_includes_soft_deleted_cascade_members(): void
    {
        $admin = $this->makeAdmin();
        $gym = $this->createGym();
        $student = $this->createStudent($gym, 'cascade-student@test.com');

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/gyms/{$gym->id}")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/gyms/{$gym->id}/members?scope=inactive")
            ->assertOk()
            ->assertJsonPath('data.students.0.id', $student->id)
            ->assertJsonPath('data.students.0.deleted_at', fn ($value) => $value !== null);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->assignRole(RoleName::Admin);

        return $admin;
    }

    private function createGym(string $slug = 'gym-test', string $status = 'active'): Gym
    {
        return Gym::query()->create([
            'name' => 'Gym '.ucfirst($slug),
            'slug' => $slug.'-'.uniqid(),
            'status' => $status,
        ]);
    }

    private function createStudent(Gym $gym, string $email = 'student@test.com'): Student
    {
        $user = User::factory()->create([
            'email' => $email,
            'status' => UserStatus::Active,
        ]);
        $user->assignRole(RoleName::Student);

        return Student::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => ProfileStatus::Active,
        ]);
    }

    private function createTrainer(Gym $gym, string $email = 'trainer@test.com'): Trainer
    {
        $user = User::factory()->create([
            'email' => $email,
            'status' => UserStatus::Active,
        ]);
        $user->assignRole(RoleName::Trainer);

        return Trainer::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => ProfileStatus::Active,
        ]);
    }
}
