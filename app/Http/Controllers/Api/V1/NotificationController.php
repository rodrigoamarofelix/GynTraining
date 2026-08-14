<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\UpdateNotificationPreferenceRequest;
use App\Http\Resources\AppNotificationResource;
use App\Http\Resources\NotificationPreferenceResource;
use App\Services\Notification\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function __construct(
        private readonly AppNotificationService $notificationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $unreadOnly = match ($request->query('read')) {
            'unread' => true,
            'read' => false,
            default => null,
        };

        return $this->paginatedResponse(
            $this->notificationService->list(
                $request->user(),
                $unreadOnly,
                (int) $request->integer('per_page', 20),
            ),
            AppNotificationResource::class,
            'Notificações recuperadas com sucesso',
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return $this->successResponse([
            'unread_count' => $this->notificationService->unreadCount($request->user()),
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $marked = $this->notificationService->markAsRead($request->user(), $notification);

        if (! $marked) {
            return $this->errorResponse('Notificação não encontrada.', 404);
        }

        return $this->successResponse(message: 'Notificação marcada como lida');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return $this->successResponse(
            ['marked_count' => $count],
            'Notificações marcadas como lidas',
        );
    }

    public function preferences(Request $request): JsonResponse
    {
        return $this->successResponse(
            new NotificationPreferenceResource($this->notificationService->preferences($request->user())),
        );
    }

    public function updatePreferences(UpdateNotificationPreferenceRequest $request): JsonResponse
    {
        $preference = $this->notificationService->updatePreferences(
            $request->user(),
            $request->validated(),
        );

        return $this->successResponse(
            new NotificationPreferenceResource($preference),
            'Preferências de notificação atualizadas com sucesso',
        );
    }
}
