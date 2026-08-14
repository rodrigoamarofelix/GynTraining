<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreExerciseRequest;
use App\Http\Requests\Api\V1\UpdateExerciseRequest;
use App\Http\Resources\ExerciseActivityLogResource;
use App\Http\Resources\ExerciseResource;
use App\Models\Exercise;
use App\Services\ExerciseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseController extends ApiController
{
    public function __construct(
        private readonly ExerciseService $exerciseService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Exercise::class);

        $filters = array_merge(
            $request->only([
                'search', 'scope', 'muscle_group_id', 'exercise_category_id', 'gym_id',
            ]),
            $this->exerciseService->filtersForUser($request->user()),
        );

        if (! isset($filters['scope'])) {
            $filters['scope'] = 'active';
        }

        $exercises = $this->exerciseService->list($filters);

        return $this->paginatedResponse($exercises, ExerciseResource::class);
    }

    public function store(StoreExerciseRequest $request): JsonResponse
    {
        $exercise = $this->exerciseService->create($request->validated(), $request->user());

        return $this->successResponse(new ExerciseResource($exercise), 'Exercício criado com sucesso', 201);
    }

    public function show(Exercise $exercise): JsonResponse
    {
        $this->authorize('view', $exercise);

        return $this->successResponse(new ExerciseResource($exercise->load(['category', 'muscleGroup', 'gym'])));
    }

    public function update(UpdateExerciseRequest $request, Exercise $exercise): JsonResponse
    {
        $exercise = $this->exerciseService->update($exercise, $request->validated(), $request->user());

        return $this->successResponse(new ExerciseResource($exercise), 'Exercício atualizado com sucesso');
    }

    public function destroy(Request $request, Exercise $exercise): JsonResponse
    {
        $this->authorize('delete', $exercise);

        $this->exerciseService->delete($exercise, $request->user());

        return $this->successResponse(message: 'Exercício removido com sucesso');
    }

    public function restore(Request $request, int $exercise): JsonResponse
    {
        $exerciseModel = $this->exerciseService->find($exercise, true);

        abort_if(! $exerciseModel, 404, 'Exercício não encontrado.');

        $this->authorize('update', $exerciseModel);

        $restored = $this->exerciseService->restore($exercise, $request->user());

        return $this->successResponse(new ExerciseResource($restored), 'Exercício reativado com sucesso');
    }

    public function activityLogs(int $exercise, Request $request): JsonResponse
    {
        $exerciseModel = $this->exerciseService->find($exercise, true);

        abort_if(! $exerciseModel, 404, 'Exercício não encontrado.');

        $this->authorize('view', $exerciseModel);

        $logs = $this->exerciseService->activityLogs(
            $exercise,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($logs, ExerciseActivityLogResource::class);
    }
}
