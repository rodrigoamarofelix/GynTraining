<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreGoalRequest;
use App\Http\Requests\Api\V1\UpdateGoalRequest;
use App\Http\Resources\GoalResource;
use App\Models\Goal;
use App\Services\Progress\GoalService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoalController extends ApiController
{
    public function __construct(
        private readonly GoalService $goalService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Goal::class);

        $filters = array_merge(
            $request->only(['student_id', 'status']),
            $this->goalService->filtersForUser($request->user()),
        );

        return $this->paginatedResponse(
            $this->goalService->list($filters, (int) $request->integer('per_page', 20)),
            GoalResource::class,
        );
    }

    public function store(StoreGoalRequest $request): JsonResponse
    {
        $goal = $this->goalService->create($request->validated());

        return $this->successResponse(
            new GoalResource($goal),
            'Meta criada com sucesso',
            201,
        );
    }

    public function show(Goal $goal): JsonResponse
    {
        $this->authorize('view', $goal);

        return $this->successResponse(
            new GoalResource($this->goalService->find($goal->id)),
        );
    }

    public function update(UpdateGoalRequest $request, Goal $goal): JsonResponse
    {
        $goal = $this->goalService->update($goal, $request->validated());

        return $this->successResponse(
            new GoalResource($goal),
            'Meta atualizada com sucesso',
        );
    }

    public function destroy(Goal $goal): JsonResponse
    {
        $this->authorize('delete', $goal);

        $this->goalService->delete($goal);

        return $this->successResponse(message: 'Meta removida com sucesso');
    }
}
