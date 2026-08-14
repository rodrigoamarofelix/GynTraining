<?php

namespace App\Http\Controllers\Api\V1;

use App\Policies\DashboardPolicy;
use App\Services\Dashboard\DashboardService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends ApiController
{
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly DashboardPolicy $dashboardPolicy,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if (! $this->dashboardPolicy->viewAny($request->user())) {
            throw new AuthorizationException;
        }

        $dashboard = $this->dashboardService->resolveForUser($request->user());

        return $this->successResponse($dashboard, 'Dashboard recuperado com sucesso');
    }
}
