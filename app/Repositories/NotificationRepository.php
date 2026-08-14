<?php

namespace App\Repositories;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Notifications\PendingStudentRegistrationNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Notifications\DatabaseNotification;

class NotificationRepository
{
    public function paginateForUser(User $user, ?bool $unreadOnly = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = $user->notifications()->latest();

        if ($unreadOnly === true) {
            $query->whereNull('read_at');
        }

        if ($unreadOnly === false) {
            $query->whereNotNull('read_at');
        }

        return $query->paginate($perPage);
    }

    public function unreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function findForUser(User $user, string $id): ?DatabaseNotification
    {
        return $user->notifications()->where('id', $id)->first();
    }

    public function markAsRead(User $user, string $id): bool
    {
        $notification = $this->findForUser($user, $id);

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function markPendingStudentRegistrationAsRead(int $studentId): int
    {
        $notifications = DatabaseNotification::query()
            ->whereNull('read_at')
            ->where('type', PendingStudentRegistrationNotification::class)
            ->get();

        $marked = 0;

        foreach ($notifications as $notification) {
            $payload = $notification->data;
            $notificationStudentId = (int) ($payload['data']['student_id'] ?? 0);

            if ($notificationStudentId !== $studentId) {
                continue;
            }

            $notification->markAsRead();
            $marked++;
        }

        return $marked;
    }
}
