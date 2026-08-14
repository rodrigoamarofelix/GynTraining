<?php

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

abstract class BaseAppNotification extends Notification implements ShouldQueue
{
    use Queueable;

    abstract public function notificationType(): NotificationType;

    abstract public function title(): string;

    abstract public function message(): string;

    abstract public function payload(): array;

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['database'];
        }

        $preference = $notifiable->notificationPreference;
        $channels = [];

        if ($preference?->database_enabled ?? true) {
            $channels[] = 'database';
        }

        if (($preference?->mail_enabled ?? true) && $this->shouldSendMail($notifiable)) {
            $channels[] = 'mail';
        }

        return $channels !== [] ? $channels : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => $this->notificationType()->value,
            'title' => $this->title(),
            'message' => $this->message(),
            'data' => $this->payload(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->title())
            ->line($this->message())
            ->line('Acesse o GynTraining para mais detalhes.');
    }

    protected function shouldSendMail(User $user): bool
    {
        return match ($this->notificationType()) {
            NotificationType::WorkoutReminder,
            NotificationType::WorkoutAvailable => $user->notificationPreference?->workout_reminders ?? true,
            NotificationType::WorkoutPlanUpdated => $user->notificationPreference?->workout_updates ?? true,
            NotificationType::InactiveStudent => $user->notificationPreference?->inactivity_alerts ?? true,
            NotificationType::PendingStudentRegistration => $user->notificationPreference?->mail_enabled ?? true,
        };
    }
}
