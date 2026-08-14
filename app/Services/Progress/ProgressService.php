<?php

namespace App\Services\Progress;

use App\Enums\WorkoutSessionStatus;
use App\Models\ExerciseLog;
use App\Models\WorkoutSession;
use App\Repositories\BodyMeasurementRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ProgressService
{
    public function __construct(
        private readonly BodyMeasurementRepository $measurementRepository,
    ) {}

    public function summary(int $studentId, ?int $exerciseId = null, ?string $period = null): array
    {
        $from = $this->resolvePeriodStart($period);

        $logsQuery = ExerciseLog::query()
            ->where('student_id', $studentId)
            ->when($from, fn ($q) => $q->where('logged_at', '>=', $from))
            ->when($exerciseId, fn ($q) => $q->where('exercise_id', $exerciseId));

        $maxLoad = (clone $logsQuery)->max('load');
        $totalVolume = (clone $logsQuery)
            ->selectRaw('COALESCE(SUM(load * repetitions), 0) as volume')
            ->value('volume');

        $workoutCount = WorkoutSession::query()
            ->where('student_id', $studentId)
            ->where('status', WorkoutSessionStatus::Completed)
            ->when($from, fn ($q) => $q->where('started_at', '>=', $from))
            ->count();

        $latestMeasurement = $this->measurementRepository->latestForStudent($studentId);
        $weightHistory = $this->measurementRepository->historyForStudent($studentId, 12);

        $exerciseEvolution = $this->exerciseEvolution($studentId, $exerciseId, $from);

        return [
            'student_id' => $studentId,
            'period' => $period,
            'max_load' => $maxLoad !== null ? (float) $maxLoad : null,
            'total_volume' => (float) $totalVolume,
            'workout_count' => $workoutCount,
            'current_weight' => $latestMeasurement?->weight !== null ? (float) $latestMeasurement->weight : null,
            'current_bmi' => $latestMeasurement?->bmi !== null ? (float) $latestMeasurement->bmi : null,
            'weight_evolution' => $weightHistory->map(fn ($item) => [
                'measured_at' => $item->measured_at?->format('Y-m-d'),
                'weight' => $item->weight !== null ? (float) $item->weight : null,
                'bmi' => $item->bmi !== null ? (float) $item->bmi : null,
            ])->values()->all(),
            'exercise_evolution' => $exerciseEvolution,
        ];
    }

    private function resolvePeriodStart(?string $period): ?Carbon
    {
        return match ($period) {
            'week' => now()->subWeek(),
            'month' => now()->subMonth(),
            '3months' => now()->subMonths(3),
            '6months' => now()->subMonths(6),
            'year' => now()->subYear(),
            default => null,
        };
    }

    private function exerciseEvolution(int $studentId, ?int $exerciseId, ?Carbon $from): array
    {
        $query = ExerciseLog::query()
            ->with('exercise')
            ->where('student_id', $studentId)
            ->when($from, fn ($q) => $q->where('logged_at', '>=', $from))
            ->when($exerciseId, fn ($q) => $q->where('exercise_id', $exerciseId))
            ->orderBy('logged_at');

        /** @var Collection<int, ExerciseLog> $logs */
        $logs = $query->get();

        return $logs
            ->groupBy('exercise_id')
            ->map(function (Collection $exerciseLogs) {
                $exercise = $exerciseLogs->first()->exercise;

                return [
                    'exercise_id' => $exercise?->id,
                    'exercise_name' => $exercise?->name,
                    'max_load' => (float) $exerciseLogs->max('load'),
                    'total_volume' => (float) $exerciseLogs->sum(fn (ExerciseLog $log) => ((float) $log->load) * ((int) $log->repetitions)),
                    'entries' => $exerciseLogs->map(fn (ExerciseLog $log) => [
                        'logged_at' => $log->logged_at?->toISOString(),
                        'set_number' => $log->set_number,
                        'repetitions' => $log->repetitions,
                        'load' => $log->load !== null ? (float) $log->load : null,
                    ])->values()->all(),
                ];
            })
            ->values()
            ->all();
    }
}
