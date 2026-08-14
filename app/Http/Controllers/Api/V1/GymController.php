<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreGymRequest;
use App\Http\Requests\Api\V1\UpdateGymRequest;
use App\Http\Resources\GymActivityLogResource;
use App\Http\Resources\GymMemberResource;
use App\Http\Resources\GymResource;
use App\Models\Gym;
use App\Services\GymService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GymController extends ApiController
{
    public function __construct(
        private readonly GymService $gymService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Gym::class);

        $filters = array_merge(
            $request->only(['search', 'status', 'scope']),
            $this->gymService->filtersForUser($request->user()),
        );

        if (! isset($filters['scope'])) {
            $filters['scope'] = 'active';
        }

        $gyms = $this->gymService->list($filters);

        return $this->paginatedResponse($gyms, GymResource::class);
    }

    public function store(StoreGymRequest $request): JsonResponse
    {
        $gym = $this->gymService->create($request->validated(), $request->user());

        return $this->successResponse(new GymResource($gym), 'Academia criada com sucesso', 201);
    }

    public function show(Gym $gym): JsonResponse
    {
        $this->authorize('view', $gym);

        return $this->successResponse(new GymResource($gym));
    }

    public function update(UpdateGymRequest $request, Gym $gym): JsonResponse
    {
        $gym = $this->gymService->update($gym, $request->validated(), $request->user());

        return $this->successResponse(new GymResource($gym), 'Academia atualizada com sucesso');
    }

    public function destroy(Request $request, Gym $gym): JsonResponse
    {
        $this->authorize('delete', $gym);

        $this->gymService->delete($gym, $request->user());

        return $this->successResponse(message: 'Academia removida com sucesso');
    }

    public function restore(Request $request, int $gym): JsonResponse
    {
        $gymModel = $this->gymService->find($gym, true);

        abort_if(! $gymModel, 404, 'Academia não encontrada.');

        $this->authorize('update', $gymModel);

        $restored = $this->gymService->restore($gym, $request->user());

        return $this->successResponse(new GymResource($restored), 'Academia reativada com sucesso');
    }

    public function activityLogs(int $gym, Request $request): JsonResponse
    {
        $gymModel = $this->gymService->find($gym, true);

        abort_if(! $gymModel, 404, 'Academia não encontrada.');

        $this->authorize('view', $gymModel);

        $logs = $this->gymService->activityLogs(
            $gym,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($logs, GymActivityLogResource::class);
    }

    public function members(int $gym, Request $request): JsonResponse
    {
        $gymModel = $this->gymService->find($gym, true);

        abort_if(! $gymModel, 404, 'Academia não encontrada.');

        $this->authorize('view', $gymModel);

        $scope = $request->string('scope', 'active')->toString();
        $members = $this->gymService->members($gym, $scope);

        return $this->successResponse([
            'trainers' => GymMemberResource::collection($members['trainers'])->resolve(),
            'students' => GymMemberResource::collection($members['students'])->resolve(),
        ]);
    }
}
