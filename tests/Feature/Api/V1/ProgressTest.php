<?php

namespace Tests\Feature\Api\V1;

use App\Enums\GoalStatus;
use App\Enums\RoleName;
use App\Enums\WorkoutSessionStatus;
use App\Models\Exercise;
use App\Models\ExerciseCategory;
use App\Models\ExerciseLog;
use App\Models\Gym;
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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProgressTest extends TestCase
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

    public function test_student_can_register_body_measurement_with_bmi(): void
    {
        [$studentUser, $student] = $this->createStudentContext();

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/body-measurements', [
                'student_id' => $student->id,
                'measured_at' => '2026-08-01',
                'weight' => 80,
                'height' => 180,
            ])
            ->assertCreated()
            ->assertJsonPath('data.weight', 80)
            ->assertJsonPath('data.bmi', 24.69);
    }

    public function test_student_can_create_goal_and_update_progress(): void
    {
        [$studentUser, $student] = $this->createStudentContext();

        $create = $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/goals', [
                'student_id' => $student->id,
                'name' => 'Perder 5 kg',
                'target' => 5,
                'current_value' => 0,
                'unit' => 'kg',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'Perder 5 kg');

        $goalId = $create->json('data.id');

        $this->actingAs($studentUser, 'sanctum')
            ->putJson("/api/v1/goals/{$goalId}", [
                'current_value' => 2.5,
                'status' => GoalStatus::Active->value,
            ])
            ->assertOk()
            ->assertJsonPath('data.current_value', 2.5)
            ->assertJsonPath('data.progress_percentage', 50);
    }

    public function test_student_can_upload_progress_photo(): void
    {
        Storage::fake('public');

        [$studentUser, $student] = $this->createStudentContext();

        $this->actingAs($studentUser, 'sanctum')
            ->post('/api/v1/progress-photos', [
                'student_id' => $student->id,
                'category' => 'front',
                'taken_at' => '2026-08-01',
                'visibility' => 'private',
                'photo' => UploadedFile::fake()->image('front.jpg'),
            ])
            ->assertCreated()
            ->assertJsonPath('data.category', 'front')
            ->assertJsonStructure(['data' => ['photo_url']]);
    }

    public function test_student_cannot_register_progress_for_another_student(): void
    {
        [$studentUser, $student] = $this->createStudentContext();

        $otherUser = User::factory()->create();
        $otherUser->assignRole(RoleName::Student);
        $otherStudent = Student::query()->create([
            'user_id' => $otherUser->id,
            'gym_id' => $student->gym_id,
            'status' => 'active',
        ]);

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/goals', [
                'student_id' => $otherStudent->id,
                'name' => 'Meta indevida',
                'target' => 10,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id']);

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/body-measurements', [
                'student_id' => $otherStudent->id,
                'measured_at' => '2026-08-01',
                'weight' => 80,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['student_id']);
    }

    public function test_student_can_view_progress_summary(): void
    {
        [$studentUser, $student, $exercise, $plan, $day] = $this->createStudentContext(withExercise: true);

        $session = WorkoutSession::query()->create([
            'student_id' => $student->id,
            'workout_plan_id' => $plan->id,
            'workout_day_id' => $day->id,
            'started_at' => now()->subHour(),
            'finished_at' => now(),
            'duration_seconds' => 3600,
            'status' => WorkoutSessionStatus::Completed,
        ]);

        ExerciseLog::query()->create([
            'workout_session_id' => $session->id,
            'exercise_id' => $exercise->id,
            'student_id' => $student->id,
            'set_number' => 1,
            'repetitions' => 10,
            'load' => 30,
            'logged_at' => now(),
        ]);

        $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/body-measurements', [
                'student_id' => $student->id,
                'measured_at' => '2026-08-10',
                'weight' => 78,
            ]);

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/progress')
            ->assertOk()
            ->assertJsonPath('data.max_load', 30)
            ->assertJsonPath('data.total_volume', 300)
            ->assertJsonPath('data.workout_count', 1)
            ->assertJsonPath('data.current_weight', 78);
    }

    public function test_delete_body_measurement_uses_soft_delete(): void
    {
        [$studentUser, $student] = $this->createStudentContext();

        $id = $this->actingAs($studentUser, 'sanctum')
            ->postJson('/api/v1/body-measurements', [
                'student_id' => $student->id,
                'measured_at' => '2026-08-01',
                'weight' => 80,
            ])
            ->json('data.id');

        $this->actingAs($studentUser, 'sanctum')
            ->deleteJson("/api/v1/body-measurements/{$id}")
            ->assertOk();

        $this->assertSoftDeleted('body_measurements', ['id' => $id]);
    }

    private function createStudentContext(bool $withExercise = false): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Progress',
            'slug' => 'gym-progress',
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

        if (! $withExercise) {
            return [$studentUser, $student];
        }

        $muscleGroup = MuscleGroup::query()->first();
        $category = ExerciseCategory::query()->first();

        $exercise = Exercise::query()->create([
            'name' => 'Supino Progresso',
            'exercise_category_id' => $category->id,
            'muscle_group_id' => $muscleGroup->id,
            'status' => 'active',
            'difficulty' => 'intermediate',
        ]);

        $plan = WorkoutPlan::query()->create([
            'student_id' => $student->id,
            'trainer_id' => $trainer->id,
            'name' => 'Ficha Progresso',
            'status' => 'active',
        ]);

        $day = WorkoutDay::query()->create([
            'workout_plan_id' => $plan->id,
            'name' => 'Treino A',
            'order' => 1,
        ]);

        return [$studentUser, $student, $exercise, $plan, $day];
    }
}
