<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\FinishWorkoutRequest;
use App\Http\Requests\Api\V1\StartWorkoutRequest;
use App\Http\Resources\WorkoutSessionResource;
use App\Models\WorkoutDay;
use App\Models\WorkoutPlan;
use App\Services\Workout\WorkoutSessionService;
use Illuminate\Http\JsonResponse;

class WorkoutExecutionController extends ApiController
{
    public function __construct(
        private readonly WorkoutSessionService $sessionService,
    ) {}

    public function start(StartWorkoutRequest $request, WorkoutPlan $workout): JsonResponse
    {
        $day = WorkoutDay::query()->findOrFail($request->validated('workout_day_id'));

        $session = $this->sessionService->start($workout, $day, $request->user());

        return $this->successResponse(
            new WorkoutSessionResource($session),
            'Treino iniciado com sucesso',
            201,
        );
    }

    public function finish(FinishWorkoutRequest $request, WorkoutPlan $workout): JsonResponse
    {
        $session = $this->sessionService->finishActiveForPlan(
            $workout,
            $request->user(),
            $request->validated('notes'),
        );

        return $this->successResponse(
            new WorkoutSessionResource($session),
            'Treino finalizado com sucesso',
        );
    }
}
