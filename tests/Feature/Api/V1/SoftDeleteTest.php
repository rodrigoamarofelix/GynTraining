<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleName;
use App\Models\Gym;
use App\Models\MuscleGroup;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SoftDeleteTest extends TestCase
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

    public function test_delete_gym_is_soft_delete(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $gym = Gym::query()->create([
            'name' => 'Gym Soft',
            'slug' => 'gym-soft',
            'status' => 'active',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/gyms/{$gym->id}")
            ->assertOk();

        $this->assertSoftDeleted('gyms', ['id' => $gym->id]);
        $this->assertDatabaseHas('gyms', ['id' => $gym->id]);
        $this->assertNull(Gym::query()->find($gym->id));
        $this->assertNotNull(Gym::withTrashed()->find($gym->id));
    }

    public function test_delete_muscle_group_is_soft_delete(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $group = MuscleGroup::query()->create([
            'name' => 'Test Group',
            'slug' => 'test-group',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/muscle-groups/{$group->id}")
            ->assertOk();

        $this->assertSoftDeleted('muscle_groups', ['id' => $group->id]);
    }

    public function test_role_model_supports_soft_delete(): void
    {
        $role = Role::query()->create([
            'name' => 'Test Role',
            'slug' => 'test-role',
        ]);

        $role->delete();

        $this->assertSoftDeleted('roles', ['id' => $role->id]);
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }
}
