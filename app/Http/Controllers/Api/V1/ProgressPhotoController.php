<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\StoreProgressPhotoRequest;
use App\Http\Requests\Api\V1\UpdateProgressPhotoRequest;
use App\Http\Resources\ProgressPhotoResource;
use App\Models\ProgressPhoto;
use App\Services\Progress\ProgressPhotoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProgressPhotoController extends ApiController
{
    public function __construct(
        private readonly ProgressPhotoService $photoService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ProgressPhoto::class);

        $filters = array_merge(
            $request->only(['student_id', 'category']),
            $this->photoService->filtersForUser($request->user()),
        );

        return $this->paginatedResponse(
            $this->photoService->list($filters, (int) $request->integer('per_page', 20)),
            ProgressPhotoResource::class,
        );
    }

    public function store(StoreProgressPhotoRequest $request): JsonResponse
    {
        $photo = $this->photoService->create(
            $request->safe()->except('photo'),
            $request->file('photo'),
        );

        return $this->successResponse(
            new ProgressPhotoResource($photo),
            'Foto de evolução registrada com sucesso',
            201,
        );
    }

    public function show(ProgressPhoto $progressPhoto): JsonResponse
    {
        $this->authorize('view', $progressPhoto);

        return $this->successResponse(
            new ProgressPhotoResource($this->photoService->find($progressPhoto->id)),
        );
    }

    public function update(UpdateProgressPhotoRequest $request, ProgressPhoto $progressPhoto): JsonResponse
    {
        $photo = $this->photoService->update(
            $progressPhoto,
            $request->safe()->except('photo'),
            $request->file('photo'),
        );

        return $this->successResponse(
            new ProgressPhotoResource($photo),
            'Foto de evolução atualizada com sucesso',
        );
    }

    public function destroy(ProgressPhoto $progressPhoto): JsonResponse
    {
        $this->authorize('delete', $progressPhoto);

        $this->photoService->delete($progressPhoto);

        return $this->successResponse(message: 'Foto de evolução removida com sucesso');
    }
}
