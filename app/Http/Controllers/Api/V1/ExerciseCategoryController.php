<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreExerciseCategoryRequest;
use App\Http\Requests\Api\V1\UpdateExerciseCategoryRequest;
use App\Http\Resources\ExerciseCategoryActivityLogResource;
use App\Http\Resources\ExerciseCategoryResource;
use App\Models\ExerciseCategory;
use App\Services\ExerciseCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExerciseCategoryController extends ApiController
{
    public function __construct(
        private readonly ExerciseCategoryService $exerciseCategoryService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ExerciseCategory::class);

        $filters = $request->only(['search', 'scope']);

        if (! isset($filters['scope'])) {
            $filters['scope'] = 'active';
        }

        $categories = $this->exerciseCategoryService->list(
            $filters,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($categories, ExerciseCategoryResource::class);
    }

    public function store(StoreExerciseCategoryRequest $request): JsonResponse
    {
        $category = $this->exerciseCategoryService->create($request->validated(), $request->user());

        return $this->successResponse(new ExerciseCategoryResource($category), 'Categoria criada com sucesso', 201);
    }

    public function show(ExerciseCategory $exerciseCategory): JsonResponse
    {
        $this->authorize('view', $exerciseCategory);

        return $this->successResponse(new ExerciseCategoryResource($exerciseCategory));
    }

    public function update(UpdateExerciseCategoryRequest $request, ExerciseCategory $exerciseCategory): JsonResponse
    {
        $category = $this->exerciseCategoryService->update($exerciseCategory, $request->validated(), $request->user());

        return $this->successResponse(new ExerciseCategoryResource($category), 'Categoria atualizada com sucesso');
    }

    public function destroy(Request $request, ExerciseCategory $exerciseCategory): JsonResponse
    {
        $this->authorize('delete', $exerciseCategory);

        $this->exerciseCategoryService->delete($exerciseCategory, $request->user());

        return $this->successResponse(message: 'Categoria removida com sucesso');
    }

    public function restore(Request $request, int $exerciseCategory): JsonResponse
    {
        $categoryModel = $this->exerciseCategoryService->find($exerciseCategory, true);

        abort_if(! $categoryModel, 404, 'Categoria não encontrada.');

        $this->authorize('update', $categoryModel);

        $restored = $this->exerciseCategoryService->restore($exerciseCategory, $request->user());

        return $this->successResponse(new ExerciseCategoryResource($restored), 'Categoria reativada com sucesso');
    }

    public function activityLogs(int $exerciseCategory, Request $request): JsonResponse
    {
        $categoryModel = $this->exerciseCategoryService->find($exerciseCategory, true);

        abort_if(! $categoryModel, 404, 'Categoria não encontrada.');

        $this->authorize('view', $categoryModel);

        $logs = $this->exerciseCategoryService->activityLogs(
            $exerciseCategory,
            (int) $request->integer('per_page', 20),
        );

        return $this->paginatedResponse($logs, ExerciseCategoryActivityLogResource::class);
    }
}
