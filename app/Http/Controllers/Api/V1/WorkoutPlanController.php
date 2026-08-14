<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreWorkoutPlanRequest;
use App\Http\Requests\Api\V1\UpdateWorkoutPlanRequest;
use App\Http\Resources\WorkoutPlanResource;
use App\Models\WorkoutPlan;
use App\Services\Workout\WorkoutPlanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkoutPlanController extends ApiController
{
    public function __construct(
        private readonly WorkoutPlanService $workoutPlanService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WorkoutPlan::class);

        $filters = array_merge(
            $request->only(['search', 'status', 'student_id', 'trainer_id', 'scope']),
            $this->workoutPlanService->filtersForUser($request->user()),
        );

        if (! isset($filters['scope'])) {
            $filters['scope'] = 'active';
        }

        return $this->paginatedResponse(
            $this->workoutPlanService->list($filters),
            WorkoutPlanResource::class,
        );
    }

    public function store(StoreWorkoutPlanRequest $request): JsonResponse
    {
        $plan = $this->workoutPlanService->create($request->validated());

        return $this->successResponse(
            new WorkoutPlanResource($plan),
            'Ficha de treino criada com sucesso',
            201,
        );
    }

    public function show(WorkoutPlan $workout): JsonResponse
    {
        $this->authorize('view', $workout);

        $plan = $this->workoutPlanService->find($workout->id, true);

        abort_if(! $plan, 404, 'Ficha não encontrada.');

        return $this->successResponse(
            new WorkoutPlanResource($plan),
        );
    }

    public function update(UpdateWorkoutPlanRequest $request, WorkoutPlan $workout): JsonResponse
    {
        $plan = $this->workoutPlanService->update($workout, $request->validated());

        return $this->successResponse(
            new WorkoutPlanResource($plan),
            'Ficha de treino atualizada com sucesso',
        );
    }

    public function destroy(WorkoutPlan $workout): JsonResponse
    {
        $this->authorize('delete', $workout);

        $this->workoutPlanService->delete($workout);

        return $this->successResponse(message: 'Ficha excluída com sucesso (deleção lógica).');
    }

    public function restore(Request $request, int $workout): JsonResponse
    {
        $plan = $this->workoutPlanService->find($workout, true);

        abort_if(! $plan, 404, 'Ficha não encontrada.');

        $this->authorize('update', $plan);

        $restored = $this->workoutPlanService->restore($workout);

        return $this->successResponse(
            new WorkoutPlanResource($restored),
            'Ficha reativada com sucesso',
        );
    }
}
