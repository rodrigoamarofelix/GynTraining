<?php

namespace Tests\Feature\Api\V1;

use App\Enums\ProfileStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Gym;
use App\Models\Trainer;
use App\Models\TrainerActivityLog;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainerActivityTest extends TestCase
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

    public function test_admin_can_view_trainer_activity_logs(): void
    {
        $admin = $this->makeAdmin();
        $trainer = $this->createTrainer();

        TrainerActivityLog::query()->create([
            'trainer_id' => $trainer->id,
            'performed_by' => $admin->id,
            'action' => 'created',
            'changes' => [],
            'summary' => 'Administrador cadastrou o professor.',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/trainers/{$trainer->id}/activity-logs")
            ->assertOk()
            ->assertJsonPath('data.0.action', 'created');
    }

    public function test_creating_trainer_logs_activity(): void
    {
        $admin = $this->makeAdmin();
        $gym = $this->createGym();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/trainers', [
            'name' => 'Carlos Personal',
            'email' => 'carlos@test.com',
            'password' => 'password123',
            'gym_id' => $gym->id,
        ])->assertCreated();

        $this->assertDatabaseHas('trainer_activity_logs', [
            'action' => 'created',
            'performed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_list_inactive_trainers_and_restore(): void
    {
        $admin = $this->makeAdmin();
        $trainer = $this->createTrainer();

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/trainers/{$trainer->id}")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/trainers?scope=inactive')
            ->assertOk()
            ->assertJsonPath('data.0.id', $trainer->id);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/trainers/{$trainer->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.status', ProfileStatus::Active->value);

        $this->assertDatabaseHas('trainer_activity_logs', [
            'trainer_id' => $trainer->id,
            'action' => 'restored',
        ]);
    }

    public function test_trainers_list_defaults_to_active_scope(): void
    {
        $admin = $this->makeAdmin();
        $active = $this->createTrainer('active@test.com', ProfileStatus::Active);
        $inactive = $this->createTrainer('inactive@test.com', ProfileStatus::Inactive);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/trainers');

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create(['status' => UserStatus::Active]);
        $admin->assignRole(RoleName::Admin);

        return $admin;
    }

    private function createGym(): Gym
    {
        return Gym::query()->create([
            'name' => 'Gym Trainer Test',
            'slug' => 'gym-trainer-'.uniqid(),
            'status' => 'active',
        ]);
    }

    private function createTrainer(
        string $email = 'trainer@test.com',
        ProfileStatus $status = ProfileStatus::Active,
    ): Trainer {
        $gym = $this->createGym();

        $user = User::factory()->create([
            'email' => $email,
            'status' => $status === ProfileStatus::Active ? UserStatus::Active : UserStatus::Inactive,
        ]);
        $user->assignRole(RoleName::Trainer);

        return Trainer::query()->create([
            'user_id' => $user->id,
            'gym_id' => $gym->id,
            'status' => $status,
        ]);
    }
}
