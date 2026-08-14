<?php

namespace Tests\Feature\Api\V1\Auth;

use App\Enums\RoleName;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_student_can_view_own_profile_but_not_other_student(): void
    {
        $student = User::factory()->create();
        $student->assignRole(RoleName::Student);

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole(RoleName::Student);

        $this->assertTrue($student->can('view', $student));
        $this->assertFalse($student->can('view', $otherStudent));
    }

    public function test_admin_can_view_any_user(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $student = User::factory()->create();
        $student->assignRole(RoleName::Student);

        $this->assertTrue($admin->can('view', $student));
    }
}
