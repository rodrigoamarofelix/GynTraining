<?php

namespace App\Services\Notification;

use App\Enums\NotificationType;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\User;
use App\Models\WorkoutPlan;
use App\Notifications\InactiveStudentNotification;
use App\Notifications\PendingStudentRegistrationNotification;
use App\Notifications\WorkoutAvailableNotification;
use App\Notifications\WorkoutPlanUpdatedNotification;
use App\Notifications\WorkoutReminderNotification;
use App\Repositories\NotificationPreferenceRepository;
use App\Repositories\NotificationRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class AppNotificationService
{
    public function __construct(
        private readonly NotificationRepository $notificationRepository,
        private readonly NotificationPreferenceRepository $preferenceRepository,
    ) {}

    public function list(User $user, ?bool $unreadOnly = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->notificationRepository->paginateForUser($user, $unreadOnly, $perPage);
    }

    public function unreadCount(User $user): int
    {
        return $this->notificationRepository->unreadCount($user);
    }

    public function markAsRead(User $user, string $notificationId): bool
    {
        return $this->notificationRepository->markAsRead($user, $notificationId);
    }

    public function markAllAsRead(User $user): int
    {
        return $this->notificationRepository->markAllAsRead($user);
    }

    public function markPendingStudentRegistrationResolved(int $studentId): void
    {
        $this->notificationRepository->markPendingStudentRegistrationAsRead($studentId);
    }

    public function preferences(User $user)
    {
        return $this->preferenceRepository->getOrCreateForUser($user);
    }

    public function updatePreferences(User $user, array $data)
    {
        $preference = $this->preferenceRepository->getOrCreateForUser($user);

        return $this->preferenceRepository->update($preference, $data);
    }

    public function send(User $user, Notification $notification): void
    {
        if (! $user->isActive()) {
            return;
        }

        $this->preferenceRepository->getOrCreateForUser($user);

        NotificationFacade::send($user, $notification);
    }

    public function sendNow(User $user, Notification $notification): void
    {
        if (! $user->isActive()) {
            return;
        }

        $this->preferenceRepository->getOrCreateForUser($user);

        NotificationFacade::sendNow($user, $notification);
    }

    public function notifyWorkoutPlanUpdated(WorkoutPlan $plan): void
    {
        $plan->loadMissing(['student.user', 'trainer.user']);
        $studentUser = $plan->student?->user;

        if (! $studentUser) {
            return;
        }

        if (! $this->allowsType($studentUser, NotificationType::WorkoutPlanUpdated)) {
            return;
        }

        $this->send($studentUser, new WorkoutPlanUpdatedNotification(
            planName: $plan->name,
            trainerName: $plan->trainer?->user?->name,
            workoutPlanId: $plan->id,
        ));
    }

    public function notifyWorkoutAvailable(User $user, string $dayName, ?int $planId = null, ?int $dayId = null): void
    {
        if (! $this->allowsType($user, NotificationType::WorkoutAvailable)) {
            return;
        }

        $this->send($user, new WorkoutAvailableNotification($dayName, $planId, $dayId));
    }

    public function notifyWorkoutReminder(User $user, string $dayName, ?int $planId = null): void
    {
        if (! $this->allowsType($user, NotificationType::WorkoutReminder)) {
            return;
        }

        $this->send($user, new WorkoutReminderNotification($dayName, $planId));
    }

    public function notifyInactiveStudent(User $user, int $daysSinceLastWorkout): void
    {
        if (! $this->allowsType($user, NotificationType::InactiveStudent)) {
            return;
        }

        $this->send($user, new InactiveStudentNotification($daysSinceLastWorkout));
    }

    public function notifyPendingStudentRegistration(Student $student): void
    {
        $student->loadMissing(['user', 'gym']);

        if (! $student->user || ! $student->gym) {
            return;
        }

        $notification = new PendingStudentRegistrationNotification(
            studentName: $student->user->name,
            studentEmail: $student->user->email,
            gymName: $student->gym->name,
            studentId: $student->id,
            gymId: $student->gym_id,
        );

        $recipients = User::query()
            ->where('status', UserStatus::Active)
            ->where(function ($query) use ($student): void {
                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('slug', RoleName::Admin->value))
                    ->orWhere(function ($gymAdminQuery) use ($student): void {
                        $gymAdminQuery
                            ->whereHas('roles', fn ($roleQuery) => $roleQuery->where('slug', RoleName::GymAdmin->value))
                            ->whereHas('gyms', fn ($gymQuery) => $gymQuery->where('gyms.id', $student->gym_id));
                    });
            })
            ->get()
            ->unique('id');

        foreach ($recipients as $recipient) {
            $this->sendNow($recipient, $notification);
        }
    }

    private function allowsType(User $user, NotificationType $type): bool
    {
        $preference = $this->preferenceRepository->getOrCreateForUser($user);

        return match ($type) {
            NotificationType::WorkoutReminder,
            NotificationType::WorkoutAvailable => $preference->workout_reminders,
            NotificationType::WorkoutPlanUpdated => $preference->workout_updates,
            NotificationType::InactiveStudent => $preference->inactivity_alerts,
        };
    }
}
