<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreTrainerRequest;
use App\Http\Requests\Api\V1\UpdateTrainerRequest;
use App\Http\Resources\TrainerActivityLogResource;
use App\Http\Resources\TrainerResource;
use App\Models\Trainer;
use App\Services\TrainerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainerController extends ApiController
{
    public function __construct(
        private readonly TrainerService $trainerService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Trainer::class);

        $filters = array_merge(
            $request->only(['search', 'scope', 'gym_id']),
            $this->trainerService->filtersForUser($request->user()),
        );

        if (! isset($filters['scope'])) {
            $filters['scope'] = 'active';
        }

        $trainers = $this->trainerService->list($filters);

        return $this->paginatedResponse($trainers, TrainerResource::class);
    }

    public function store(StoreTrainerRequest $request): JsonResponse
    {
        $trainer = $this->trainerService->create($request->validated(), $request->user());

        return $this->successResponse(new TrainerResource($trainer), 'Professor criado com sucesso', 201);
    }

    public function show(Trainer $trainer): JsonResponse
    {
        $this->authorize('view', $trainer);

        return $this->successResponse(new TrainerResource($trainer->load(['user', 'gym'])));
    }

    public function update(UpdateTrainerRequest $request, Trainer $trainer): JsonResponse
    {
        $trainer = $this->trainerService->update($trainer, $request->validated(), $request->user());

        return $this->successResponse(new TrainerResource($trainer), 'Professor atualizado com sucesso');
    }

    public function destroy(Request $request, Trainer $trainer): JsonResponse
    {
        $this->authorize('delete', $trainer);

        $this->trainerService->delete($trainer, $request->user());

        return $this->successResponse(message: 'Professor removido com sucesso');
    }

    public function restore(Request $request, int $trainer): JsonResponse
    {
        $trainerModel = $this->trainerService->find($trainer, true);

        abort_if(! $trainerModel, 404, 'Professor não encontrado.');

        $this->authorize('update', $trainerModel);

        $restored = $this->trainerService->restore($trainer, $request->user());

        return $this->successResponse(new TrainerResource($restored), 'Professor reativado com sucesso');
    }

    public function activityLogs(int $trainer, Request $request): JsonResponse
    {
        $trainerModel = $this->trainerService->find($trainer, true);

        abort_if(! $trainerModel, 404, 'Professor não encontrado.');

        $this->authorize('view', $trainerModel);

        $logs = $this->trainerService->activityLogs(
            $trainer,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($logs, TrainerActivityLogResource::class);
    }
}
