<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreMuscleGroupRequest;
use App\Http\Requests\Api\V1\UpdateMuscleGroupRequest;
use App\Http\Resources\MuscleGroupActivityLogResource;
use App\Http\Resources\MuscleGroupResource;
use App\Models\MuscleGroup;
use App\Services\MuscleGroupService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MuscleGroupController extends ApiController
{
    public function __construct(
        private readonly MuscleGroupService $muscleGroupService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', MuscleGroup::class);

        $filters = $request->only(['search', 'scope']);

        if (! isset($filters['scope'])) {
            $filters['scope'] = 'active';
        }

        $muscleGroups = $this->muscleGroupService->list(
            $filters,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($muscleGroups, MuscleGroupResource::class);
    }

    public function store(StoreMuscleGroupRequest $request): JsonResponse
    {
        $muscleGroup = $this->muscleGroupService->create($request->validated(), $request->user());

        return $this->successResponse(new MuscleGroupResource($muscleGroup), 'Grupo muscular criado com sucesso', 201);
    }

    public function show(MuscleGroup $muscleGroup): JsonResponse
    {
        $this->authorize('view', $muscleGroup);

        return $this->successResponse(new MuscleGroupResource($muscleGroup));
    }

    public function update(UpdateMuscleGroupRequest $request, MuscleGroup $muscleGroup): JsonResponse
    {
        $muscleGroup = $this->muscleGroupService->update($muscleGroup, $request->validated(), $request->user());

        return $this->successResponse(new MuscleGroupResource($muscleGroup), 'Grupo muscular atualizado com sucesso');
    }

    public function destroy(Request $request, MuscleGroup $muscleGroup): JsonResponse
    {
        $this->authorize('delete', $muscleGroup);

        $this->muscleGroupService->delete($muscleGroup, $request->user());

        return $this->successResponse(message: 'Grupo muscular removido com sucesso');
    }

    public function restore(Request $request, int $muscleGroup): JsonResponse
    {
        $muscleGroupModel = $this->muscleGroupService->find($muscleGroup, true);

        abort_if(! $muscleGroupModel, 404, 'Grupo muscular não encontrado.');

        $this->authorize('update', $muscleGroupModel);

        $restored = $this->muscleGroupService->restore($muscleGroup, $request->user());

        return $this->successResponse(new MuscleGroupResource($restored), 'Grupo muscular reativado com sucesso');
    }

    public function activityLogs(int $muscleGroup, Request $request): JsonResponse
    {
        $muscleGroupModel = $this->muscleGroupService->find($muscleGroup, true);

        abort_if(! $muscleGroupModel, 404, 'Grupo muscular não encontrado.');

        $this->authorize('view', $muscleGroupModel);

        $logs = $this->muscleGroupService->activityLogs(
            $muscleGroup,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($logs, MuscleGroupActivityLogResource::class);
    }
}
