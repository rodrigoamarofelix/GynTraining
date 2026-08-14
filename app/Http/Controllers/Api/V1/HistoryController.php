<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\RoleName;
use App\Http\Resources\ExerciseLogResource;
use App\Models\ExerciseLog;
use App\Models\Student;
use App\Services\Workout\WorkoutSessionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends ApiController
{
    public function __construct(
        private readonly WorkoutSessionService $sessionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ExerciseLog::class);

        $user = $request->user();
        $studentId = $this->resolveStudentId($request, $user);

        return $this->paginatedResponse(
            $this->sessionService->history(
                $studentId,
                $request->integer('exercise_id') ?: null,
                (int) $request->integer('per_page', 20),
            ),
            ExerciseLogResource::class,
            'Histórico recuperado com sucesso',
        );
    }

    private function resolveStudentId(Request $request, $user): int
    {
        if ($user->hasRole(RoleName::Student) && $user->student) {
            return $user->student->id;
        }

        $studentId = $request->integer('student_id');

        if (! $studentId) {
            throw new AuthorizationException('Informe o aluno para consultar o histórico.');
        }

        $student = Student::query()->findOrFail($studentId);

        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            if ($student->gym_id !== $user->trainer->gym_id) {
                throw new AuthorizationException;
            }

            $allowed = $student->trainer_id === $user->trainer->id
                || $student->workoutPlans()->where('trainer_id', $user->trainer->id)->exists();

            if (! $allowed) {
                throw new AuthorizationException;
            }

            return $studentId;
        }

        if ($user->hasRole(RoleName::Admin)) {
            return $studentId;
        }

        throw new AuthorizationException;
    }
}
