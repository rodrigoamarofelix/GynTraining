<?php

namespace App\Traits;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\AbstractPaginator;

trait ApiResponse
{
    protected function successResponse(
        mixed $data = null,
        string $message = 'Operação realizada com sucesso',
        int $status = 200,
    ): JsonResponse {
        $response = [
            'success' => true,
            'message' => $message,
        ];

        if ($data instanceof AbstractPaginator) {
            $response['data'] = $this->resolveResponseData($data->items());
            $response['meta'] = [
                'current_page' => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
            ];

            return response()->json($response, $status);
        }

        if ($data !== null) {
            $response['data'] = $this->resolveResponseData($data);
        }

        return response()->json($response, $status);
    }

    protected function errorResponse(
        string $message = 'Não foi possível realizar a operação',
        int $status = 400,
        ?array $errors = null,
    ): JsonResponse {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $status);
    }

    protected function paginatedResponse(
        AbstractPaginator $paginator,
        string $resourceClass,
        string $message = 'Operação realizada com sucesso',
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $resourceClass::collection($paginator->items())->resolve(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function resolveResponseData(mixed $data): mixed
    {
        if ($data instanceof JsonResource || $data instanceof ResourceCollection) {
            return $data->resolve();
        }

        return $data;
    }
}
