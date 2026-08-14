<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreWorkoutExerciseRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutExerciseRequest;
use App\Http\Resources\WorkoutExerciseResource;
use App\Models\WorkoutExercise;
use App\Services\Workout\WorkoutExerciseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutExerciseController extends ApiController
{
    public function __construct(
        private readonly WorkoutExerciseService $workoutExerciseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutExercise::class);

        return $this->paginatedResponse(
            $this->workoutExerciseService->list($request->only(['workout_day_id'])),
            WorkoutExerciseResource::class,
        );
    }

    public function store(StoreWorkoutExerciseRequest $request): JsonResponse
    {
        $workoutExercise = $this->workoutExerciseService->create($request->validated());

        return $this->successResponse(
            new WorkoutExerciseResource($workoutExercise),
            'Exercício adicionado ao treino com sucesso',
            201,
        );
    }

    public function show(WorkoutExercise $workoutExercise): JsonResponse
    {
        $this->authorize('view', $workoutExercise);

        return $this->successResponse(
            new WorkoutExerciseResource($this->workoutExerciseService->find($workoutExercise->id)),
        );
    }

    public function update(UpdateWorkoutExerciseRequest $request, WorkoutExercise $workoutExercise): JsonResponse
    {
        $workoutExercise = $this->workoutExerciseService->update($workoutExercise, $request->validated());

        return $this->successResponse(
            new WorkoutExerciseResource($workoutExercise),
            'Exercício do treino atualizado com sucesso',
        );
    }

    public function destroy(WorkoutExercise $workoutExercise): JsonResponse
    {
        $this->authorize('delete', $workoutExercise);

        $this->workoutExerciseService->delete($workoutExercise);

        return $this->successResponse(message: 'Exercício do treino removido com sucesso');
    }
}
