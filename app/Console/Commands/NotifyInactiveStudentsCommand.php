<?php

namespace App\Console\Commands;

use App\Models\Student;
use App\Repositories\DashboardRepository;
use App\Services\Notification\AppNotificationService;
use Illuminate\Console\Command;

class NotifyInactiveStudentsCommand extends Command
{
    protected $signature = 'notifications:notify-inactive-students {--days=5 : Dias sem treinar para alertar}';

    protected $description = 'Notifica alunos inativos há vários dias sem treinar';

    public function handle(
        DashboardRepository $dashboardRepository,
        AppNotificationService $notificationService,
    ): int {
        $threshold = (int) $this->option('days');
        $students = Student::query()->with('user')->get();
        $sent = 0;

        foreach ($students as $student) {
            $user = $student->user;

            if (! $user) {
                continue;
            }

            $lastSession = $dashboardRepository->lastCompletedSessionForStudent($student->id);

            if (! $lastSession?->finished_at) {
                continue;
            }

            $days = (int) $lastSession->finished_at->diffInDays(now());

            if ($days < $threshold) {
                continue;
            }

            $notificationService->notifyInactiveStudent($user, $days);
            $sent++;
        }

        $this->info("Alertas de inatividade enviados para {$sent} aluno(s).");

        return self::SUCCESS;
    }
}
