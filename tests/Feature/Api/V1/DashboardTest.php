<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleName;
use App\Enums\WorkoutSessionStatus;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\Gym;
use App\Models\Goal;
use App\Models\MuscleGroup;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutPlan;
use App\Models\WorkoutSession;
use Database\Seeders\ExerciseCategorySeeder;
use Database\Seeders\MuscleGroupSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_student_dashboard_returns_workout_and_stats(): void
    {
        [$studentUser, $student, $plan, $dayA, $dayB] = $this->createStudentWithWorkoutPlan();

        WorkoutSession::query()->create([
            'student_id' => $student->id,
            'workout_plan_id' => $plan->id,
            'workout_day_id' => $dayA->id,
            'started_at' => now()->subDays(2),
            'finished_at' => now()->subDays(2)->addHour(),
            'duration_seconds' => 3600,
            'status' => WorkoutSessionStatus::Completed,
        ]);

        Goal::query()->create([
            'student_id' => $student->id,
            'name' => 'Treinar 4x por semana',
            'target' => 4,
            'current_value' => 2,
            'unit' => 'treinos/semana',
            'status' => 'active',
        ]);

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'student')
            ->assertJsonPath('data.next_workout.name', 'Treino B')
            ->assertJsonPath('data.last_workout.workout_day_name', 'Treino A')
            ->assertJsonPath('data.stats.workouts_this_month', 1)
            ->assertJsonCount(1, 'data.goals');
    }

    public function test_trainer_dashboard_returns_students_and_alerts(): void
    {
        [$trainerUser, $student, $plan, $day] = $this->createTrainerWithStudent();

        WorkoutSession::query()->create([
            'student_id' => $student->id,
            'workout_plan_id' => $plan->id,
            'workout_day_id' => $day->id,
            'started_at' => now()->subDays(10),
            'finished_at' => now()->subDays(10)->addHour(),
            'duration_seconds' => 3600,
            'status' => WorkoutSessionStatus::Completed,
        ]);

        $this->actingAs($trainerUser, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'trainer')
            ->assertJsonPath('data.stats.total_students', 1)
            ->assertJsonPath('data.stats.students_without_workout', 1)
            ->assertJsonCount(1, 'data.alerts');
    }

    public function test_admin_dashboard_returns_global_stats(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleName::Admin);

        $this->actingAs($admin, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', 'admin')
            ->assertJsonStructure([
                'data' => [
                    'stats' => [
                        'total_users',
                        'total_students',
                        'total_trainers',
                        'total_gyms',
                        'workouts_this_month',
                    ],
                ],
            ]);
    }

    public function test_trainer_dashboard_excludes_students_from_other_gyms(): void
    {
        [$trainerUser, $student, $plan, $day] = $this->createTrainerWithStudent();

        $otherGym = Gym::query()->create([
            'name' => 'Outra Academia',
            'slug' => 'outra-academia',
            'status' => 'active',
        ]);

        $otherStudentUser = User::factory()->create(['name' => 'Rodrigo Amaro']);
        $otherStudentUser->assignRole(RoleName::Student);
        $otherStudent = Student::query()->create([
            'user_id' => $otherStudentUser->id,
            'gym_id' => $otherGym->id,
            'status' => 'active',
        ]);

        WorkoutPlan::query()->create([
            'student_id' => $otherStudent->id,
            'trainer_id' => $plan->trainer_id,
            'name' => 'Ficha indevida',
            'status' => 'active',
        ]);

        $this->actingAs($trainerUser, 'sanctum')
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.stats.total_students', 1);
    }

    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        $this->getJson('/api/v1/dashboard')->assertUnauthorized();
    }

    private function createStudentWithWorkoutPlan(): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Dashboard',
            'slug' => 'gym-dashboard',
            'status' => 'active',
        ]);

        $trainerUser = User::factory()->create();
        $trainerUser->assignRole(RoleName::Trainer);
        $trainer = Trainer::query()->create([
            'user_id' => $trainerUser->id,
            'gym_id' => $gym->id,
            'status' => 'active',
        ]);

        $studentUser = User::factory()->create();
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
            'name' => 'Ficha ABC',
            'status' => 'active',
        ]);

        $dayA = WorkoutDay::query()->create([
            'workout_plan_id' => $plan->id,
            'name' => 'Treino A',
            'order' => 1,
        ]);

        $dayB = WorkoutDay::query()->create([
            'workout_plan_id' => $plan->id,
            'name' => 'Treino B',
            'order' => 2,
        ]);

        return [$studentUser, $student, $plan, $dayA, $dayB];
    }

    private function createTrainerWithStudent(): array
    {
        [$studentUser, $student, $plan, $dayA] = $this->createStudentWithWorkoutPlan();

        $trainer = Trainer::query()->where('id', $plan->trainer_id)->first();
        $trainerUser = User::query()->find($trainer->user_id);
        $trainerUser->setRelation('trainer', $trainer);

        return [$trainerUser, $student, $plan, $dayA];
    }
}
