<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\FinishWorkoutSessionRequest;
use App\Http\Requests\Api\V1\StoreWorkoutSessionRequest;
use App\Http\Resources\ExerciseLogResource;
use App\Http\Resources\WorkoutSessionResource;
use App\Models\WorkoutSession;
use App\Services\Workout\WorkoutSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutSessionController extends ApiController
{
    public function __construct(
        private readonly WorkoutSessionService $sessionService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutSession::class);

        $filters = array_merge(
            $request->only(['status', 'student_id', 'workout_plan_id']),
            $this->sessionService->filtersForUser($request->user()),
        );

        return $this->paginatedResponse(
            $this->sessionService->list($filters, (int) $request->integer('per_page', 20)),
            WorkoutSessionResource::class,
        );
    }

    public function show(WorkoutSession $workoutSession): JsonResponse
    {
        $this->authorize('view', $workoutSession);

        return $this->successResponse(
            new WorkoutSessionResource($this->sessionService->find($workoutSession->id)),
        );
    }

    public function store(StoreWorkoutSessionRequest $request): JsonResponse
    {
        $session = WorkoutSession::query()->findOrFail($request->validated('workout_session_id'));

        $log = $this->sessionService->logSet($session, $request->validated());

        return $this->successResponse(
            new ExerciseLogResource($log),
            'Série registrada com sucesso',
            201,
        );
    }

    public function finish(FinishWorkoutSessionRequest $request, WorkoutSession $workoutSession): JsonResponse
    {
        $session = $this->sessionService->finish(
            $workoutSession,
            $request->validated('notes'),
        );

        return $this->successResponse(
            new WorkoutSessionResource($session),
            'Treino finalizado com sucesso',
        );
    }

    public function cancel(FinishWorkoutSessionRequest $request, WorkoutSession $workoutSession): JsonResponse
    {
        $this->authorize('finish', $workoutSession);

        $session = $this->sessionService->cancel(
            $workoutSession,
            $request->validated('notes'),
        );

        return $this->successResponse(
            new WorkoutSessionResource($session),
            'Treino cancelado com sucesso',
        );
    }
}
