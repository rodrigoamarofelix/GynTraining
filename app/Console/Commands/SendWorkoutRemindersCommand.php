<?php

namespace App\Console\Commands;

use App\Enums\WorkoutPlanStatus;
use App\Models\Student;
use App\Repositories\DashboardRepository;
use App\Services\Notification\AppNotificationService;
use Illuminate\Console\Command;

class SendWorkoutRemindersCommand extends Command
{
    protected $signature = 'notifications:send-workout-reminders';

    protected $description = 'Envia lembretes de treino para alunos com ficha ativa';

    public function handle(
        DashboardRepository $dashboardRepository,
        AppNotificationService $notificationService,
    ): int {
        $students = Student::query()
            ->with(['user.notificationPreference'])
            ->whereHas('workoutPlans', fn ($q) => $q->where('status', WorkoutPlanStatus::Active))
            ->get();

        $sent = 0;

        foreach ($students as $student) {
            $user = $student->user;

            if (! $user) {
                continue;
            }

            $preference = $notificationService->preferences($user);

            if (! $preference->workout_reminders) {
                continue;
            }

            $plan = $dashboardRepository->activeWorkoutPlanForStudent($student->id);

            if (! $plan) {
                continue;
            }

            $lastSession = $dashboardRepository->lastCompletedSessionForStudent($student->id);
            $activeSession = $dashboardRepository->activeSessionForStudent($student->id);

            if ($activeSession) {
                continue;
            }

            $days = $plan->days->sortBy('order')->values();
            $nextDay = $days->first();

            if ($lastSession?->workout_day_id) {
                $lastIndex = $days->search(fn ($day) => $day->id === $lastSession->workout_day_id);

                if ($lastIndex !== false) {
                    $nextDay = $days[($lastIndex + 1) % $days->count()];
                }
            }

            if (! $nextDay) {
                continue;
            }

            $notificationService->notifyWorkoutReminder($user, $nextDay->name, $plan->id);
            $notificationService->notifyWorkoutAvailable($user, $nextDay->name, $plan->id, $nextDay->id);
            $sent++;
        }

        $this->info("Lembretes enviados para {$sent} aluno(s).");

        return self::SUCCESS;
    }
}
