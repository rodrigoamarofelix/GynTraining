<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleName;
use App\Models\ExerciseCategory;
use App\Models\ExerciseCategoryActivityLog;
use App\Models\User;
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExerciseCategoryActivityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed([
            RoleSeeder::class,
            PermissionSeeder::class,
            ExerciseCategorySeeder::class,
        ]);
    }

    public function test_admin_can_view_exercise_category_activity_logs(): void
    {
        $admin = $this->makeAdmin();
        $category = ExerciseCategory::query()->first();

        ExerciseCategoryActivityLog::query()->create([
            'exercise_category_id' => $category->id,
            'performed_by' => $admin->id,
            'action' => 'created',
            'changes' => [],
            'summary' => 'Administrador cadastrou a categoria.',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->getJson("/api/v1/exercise-categories/{$category->id}/activity-logs")
            ->assertOk()
            ->assertJsonPath('data.0.action', 'created');
    }

    public function test_creating_exercise_category_logs_activity(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin, 'sanctum')->postJson('/api/v1/exercise-categories', [
            'name' => 'Funcional',
            'description' => 'Movimentos funcionais',
        ])->assertCreated();

        $this->assertDatabaseHas('exercise_category_activity_logs', [
            'action' => 'created',
            'performed_by' => $admin->id,
        ]);
    }

    public function test_admin_can_list_inactive_categories_and_restore(): void
    {
        $admin = $this->makeAdmin();
        $category = ExerciseCategory::query()->create([
            'name' => 'Categoria Teste',
            'slug' => 'categoria-teste',
            'description' => 'Teste',
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/exercise-categories/{$category->id}")
            ->assertOk();

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/exercise-categories?scope=inactive')
            ->assertOk()
            ->assertJsonPath('data.0.id', $category->id);

        $this->actingAs($admin, 'sanctum')
            ->postJson("/api/v1/exercise-categories/{$category->id}/restore")
            ->assertOk()
            ->assertJsonPath('data.name', 'Categoria Teste');

        $this->assertDatabaseHas('exercise_category_activity_logs', [
            'exercise_category_id' => $category->id,
            'action' => 'restored',
        ]);
    }

    public function test_exercise_categories_list_defaults_to_active_scope(): void
    {
        $admin = $this->makeAdmin();
        $active = ExerciseCategory::query()->create([
            'name' => 'Ativa',
            'slug' => 'ativa-'.uniqid(),
        ]);
        $inactive = ExerciseCategory::query()->create([
            'name' => 'Inativa',
            'slug' => 'inativa-'.uniqid(),
        ]);
        $inactive->delete();

        $response = $this->actingAs($admin, 'sanctum')->getJson('/api/v1/exercise-categories');

        $ids = collect($response->json('data'))->pluck('id');

        $this->assertTrue($ids->contains($active->id));
        $this->assertFalse($ids->contains($inactive->id));
    }

    public function test_delete_exercise_category_is_soft_delete(): void
    {
        $admin = $this->makeAdmin();
        $category = ExerciseCategory::query()->create([
            'name' => 'Soft Delete',
            'slug' => 'soft-delete-'.uniqid(),
        ]);

        $this->actingAs($admin, 'sanctum')
            ->deleteJson("/api/v1/exercise-categories/{$category->id}")
            ->assertOk();

        $this->assertSoftDeleted('exercise_categories', ['id' => $category->id]);
    }

    private function makeAdmin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        return $admin;
    }
}
