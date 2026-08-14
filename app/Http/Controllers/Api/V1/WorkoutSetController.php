<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreWorkoutSetRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutSetRequest;
use App\Http\Resources\WorkoutSetResource;
use App\Models\WorkoutSet;
use App\Services\Workout\WorkoutSetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutSetController extends ApiController
{
    public function __construct(
        private readonly WorkoutSetService $workoutSetService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutSet::class);

        return $this->paginatedResponse(
            $this->workoutSetService->list($request->only(['workout_exercise_id'])),
            WorkoutSetResource::class,
        );
    }

    public function store(StoreWorkoutSetRequest $request): JsonResponse
    {
        $set = $this->workoutSetService->create($request->validated());

        return $this->successResponse(
            new WorkoutSetResource($set),
            'Série adicionada com sucesso',
            201,
        );
    }

    public function show(WorkoutSet $workoutSet): JsonResponse
    {
        $this->authorize('view', $workoutSet);

        return $this->successResponse(
            new WorkoutSetResource($this->workoutSetService->find($workoutSet->id)),
        );
    }

    public function update(UpdateWorkoutSetRequest $request, WorkoutSet $workoutSet): JsonResponse
    {
        $set = $this->workoutSetService->update($workoutSet, $request->validated());

        return $this->successResponse(
            new WorkoutSetResource($set),
            'Série atualizada com sucesso',
        );
    }

    public function destroy(WorkoutSet $workoutSet): JsonResponse
    {
        $this->authorize('delete', $workoutSet);

        $this->workoutSetService->delete($workoutSet);

        return $this->successResponse(message: 'Série removida com sucesso');
    }
}
