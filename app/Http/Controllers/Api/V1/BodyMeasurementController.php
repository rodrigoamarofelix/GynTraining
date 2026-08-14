<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreBodyMeasurementRequest;
use App\Http\Requests\Api\V1\UpdateBodyMeasurementRequest;
use App\Http\Resources\BodyMeasurementResource;
use App\Models\BodyMeasurement;
use App\Services\Progress\BodyMeasurementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BodyMeasurementController extends ApiController
{
    public function __construct(
        private readonly BodyMeasurementService $measurementService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', BodyMeasurement::class);

        $filters = array_merge(
            $request->only(['student_id']),
            $this->measurementService->filtersForUser($request->user()),
        );

        return $this->paginatedResponse(
            $this->measurementService->list($filters, (int) $request->integer('per_page', 20)),
            BodyMeasurementResource::class,
        );
    }

    public function store(StoreBodyMeasurementRequest $request): JsonResponse
    {
        $measurement = $this->measurementService->create($request->validated());

        return $this->successResponse(
            new BodyMeasurementResource($measurement),
            'Medida corporal registrada com sucesso',
            201,
        );
    }

    public function show(BodyMeasurement $bodyMeasurement): JsonResponse
    {
        $this->authorize('view', $bodyMeasurement);

        return $this->successResponse(
            new BodyMeasurementResource($this->measurementService->find($bodyMeasurement->id)),
        );
    }

    public function update(UpdateBodyMeasurementRequest $request, BodyMeasurement $bodyMeasurement): JsonResponse
    {
        $measurement = $this->measurementService->update($bodyMeasurement, $request->validated());

        return $this->successResponse(
            new BodyMeasurementResource($measurement),
            'Medida corporal atualizada com sucesso',
        );
    }

    public function destroy(BodyMeasurement $bodyMeasurement): JsonResponse
    {
        $this->authorize('delete', $bodyMeasurement);

        $this->measurementService->delete($bodyMeasurement);

        return $this->successResponse(message: 'Medida corporal removida com sucesso');
    }
}
