<?php

namespace Tests\Feature\Api\V1;

use App\Enums\RoleName;
use App\Models\Gym;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Models\WorkoutDay;
use App\Models\WorkoutPlan;
use App\Notifications\InactiveStudentNotification;
use App\Notifications\WorkoutPlanUpdatedNotification;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationTest extends TestCase
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

    public function test_user_can_list_and_mark_notifications_as_read(): void
    {
        [$studentUser, $plan] = $this->createStudentWithPlan();

        $studentUser->notify(new WorkoutPlanUpdatedNotification(
            planName: $plan->name,
            workoutPlanId: $plan->id,
        ));

        $listResponse = $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'workout_plan_updated');

        $notificationId = $listResponse->json('data.0.id');

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 1);

        $this->actingAs($studentUser, 'sanctum')
            ->postJson("/api/v1/notifications/{$notificationId}/read")
            ->assertOk();

        $this->actingAs($studentUser, 'sanctum')
            ->getJson('/api/v1/notifications/unread-count')
            ->assertJsonPath('data.unread_count', 0);
    }

    public function test_workout_plan_update_notifies_student(): void
    {
        Notification::fake();

        [$trainerUser, $student, $plan] = $this->createTrainerStudentAndPlan();
        $studentUser = $student->user;

        $this->actingAs($trainerUser, 'sanctum')
            ->putJson("/api/v1/workouts/{$plan->id}", [
                'name' => 'Ficha Atualizada',
                'description' => 'Nova descrição',
            ])
            ->assertOk();

        Notification::assertSentTo($studentUser, WorkoutPlanUpdatedNotification::class);
    }

    public function test_user_can_update_notification_preferences(): void
    {
        $studentUser = User::factory()->create();
        $studentUser->assignRole(RoleName::Student);

        $this->actingAs($studentUser, 'sanctum')
            ->putJson('/api/v1/notification-preferences', [
                'mail_enabled' => false,
                'workout_reminders' => false,
            ])
            ->assertOk()
            ->assertJsonPath('data.mail_enabled', false)
            ->assertJsonPath('data.workout_reminders', false);
    }

    public function test_inactive_students_command_sends_notification(): void
    {
        Notification::fake();

        [$studentUser, $plan, $day] = $this->createStudentWithPlanAndDay();
        $student = Student::query()->where('user_id', $studentUser->id)->first();

        \App\Models\WorkoutSession::query()->create([
            'student_id' => $student->id,
            'workout_plan_id' => $plan->id,
            'workout_day_id' => $day->id,
            'started_at' => now()->subDays(10),
            'finished_at' => now()->subDays(10)->addHour(),
            'duration_seconds' => 3600,
            'status' => \App\Enums\WorkoutSessionStatus::Completed,
        ]);

        Artisan::call('notifications:notify-inactive-students', ['--days' => 5]);

        Notification::assertSentTo($studentUser, InactiveStudentNotification::class);
    }

    private function createStudentWithPlan(): array
    {
        $gym = Gym::query()->create([
            'name' => 'Gym Notify',
            'slug' => 'gym-notify',
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
            'name' => 'Ficha Notify',
            'status' => 'active',
        ]);

        return [$studentUser, $plan];
    }

    private function createStudentWithPlanAndDay(): array
    {
        [$studentUser, $plan] = $this->createStudentWithPlan();

        $day = WorkoutDay::query()->create([
            'workout_plan_id' => $plan->id,
            'name' => 'Treino A',
            'order' => 1,
        ]);

        return [$studentUser, $plan, $day];
    }

    private function createTrainerStudentAndPlan(): array
    {
        [$studentUser, $plan] = $this->createStudentWithPlan();
        $student = $studentUser->student;
        $trainer = Trainer::query()->find($plan->trainer_id);
        $trainerUser = User::query()->find($trainer->user_id);
        $trainerUser->setRelation('trainer', $trainer);

        return [$trainerUser, $student, $plan];
    }
}
