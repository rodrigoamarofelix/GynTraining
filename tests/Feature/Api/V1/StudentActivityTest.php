<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProfileStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Gym;
use App\Models\Student;
use App\Models\StudentActivityLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudentActivityTest extends TestCase
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

    public function test_admin_can_view_student_activity_logs(): void
    {
        $admin = $this->makeAdmin();
        [$student] = $this->createStudent();

        StudentActivityLog::query()->create([
            'student_id' => $student->id,
            'performed_by' => $admin->id,
            'action' => 'created',
            'changes' => [
                ['field' => 'name', 'label' => 'Nome', 'old' => null, 'new' => 'Maria Aluna'],
            ],
            'summary' => 'Administrador cadastrou o aluno.',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/students/{$student->id}/activity-logs")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.action', 'created')
            ->assertJsonPath('data.0.performer.name', $admin->name);
    }

    public function test_updating_student_creates_activity_log(): void
    {
        $admin = $this->makeAdmin();
        [$student] = $this->createStudent();

        $this->actingAs($admin, 'sanctum')
            ->putJson("/api/v1/students/{$student->id}", [
                'name' => 'Maria Atualizada',
            ])
            ->assertOk();

        $this->assertDatabaseHas('student_activity_logs', [
            'student_id' => $student->id,
            'performed_by' => $admin->id,
            'action' => 'updated',
        ]);
    }

    public function test_admin_can_list_inactive_students_and_restore(): void
    {
        $admin = $this->makeAdmin();
        [$student] = $this->createStudent();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/students/{$student->id}")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/students?scope=inactive')
            ->assertOk()
            ->assertJsonPath('data.0.id', $student->id);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/students/{$student->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', ProfileStatus::Active->value);

        $student->refresh();
        $this->assertNull($student->deleted_at);
        $this->assertSame(ProfileStatus::Active, $student->status);

        $this->assertDatabaseHas('student_activity_logs', [
            'student_id' => $student->id,
            'action' => 'restored',
        ]);
    }

    public function test_students_list_defaults_to_active_scope(): void
    {
        $admin = $this->makeAdmin();
        [$activeStudent] = $this->createStudent('active@test.com');
        [$pendingStudent] = $this->createStudent('pending@test.com', ProfileStatus::Pending);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/students');

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($activeStudent->id));
        $this->assertFalse($ids->contains($pendingStudent->id));
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->assignRole(RoleName::Admin);

        return $admin;
    }

    /** @return array{0: Student} */
    private function createStudent(string $email = 'student@test.com', ProfileStatus $status = ProfileStatus::Active): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Activity',
            'slug' => 'gym-activity-'.uniqid(),
            'status' => 'active',
        ]);

        $user = User::factory()->create([
            'email' => $email,
            'status' => $status === ProfileStatus::Active ? UserStatus::Active : UserStatus::Inactive,
        ]);
        $user->assignRole(RoleName::Student);

        $student = Student::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => $status,
        ]);

        return [$student];
    }
}
