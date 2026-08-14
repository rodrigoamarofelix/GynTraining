<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreWorkoutDayRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutDayRequest;
use App\Http\Resources\WorkoutDayResource;
use App\Models\WorkoutDay;
use App\Services\Workout\WorkoutDayService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutDayController extends ApiController
{
    public function __construct(
        private readonly WorkoutDayService $workoutDayService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutDay::class);

        return $this->paginatedResponse(
            $this->workoutDayService->list($request->only(['workout_plan_id'])),
            WorkoutDayResource::class,
        );
    }

    public function store(StoreWorkoutDayRequest $request): JsonResponse
    {
        $day = $this->workoutDayService->create($request->validated());

        return $this->successResponse(
            new WorkoutDayResource($day),
            'Dia de treino criado com sucesso',
            201,
        );
    }

    public function show(WorkoutDay $workoutDay): JsonResponse
    {
        $this->authorize('view', $workoutDay);

        return $this->successResponse(
            new WorkoutDayResource($this->workoutDayService->find($workoutDay->id)),
        );
    }

    public function update(UpdateWorkoutDayRequest $request, WorkoutDay $workoutDay): JsonResponse
    {
        $day = $this->workoutDayService->update($workoutDay, $request->validated());

        return $this->successResponse(
            new WorkoutDayResource($day),
            'Dia de treino atualizado com sucesso',
        );
    }

    public function destroy(WorkoutDay $workoutDay): JsonResponse
    {
        $this->authorize('delete', $workoutDay);

        $this->workoutDayService->delete($workoutDay);

        return $this->successResponse(message: 'Dia de treino removido com sucesso');
    }
}
