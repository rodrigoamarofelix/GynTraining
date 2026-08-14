<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Enums\GymStatus;
use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\Auth\ForgotPasswordRequest;
use App\Http\Requests\Api\V1\Auth\LoginRequest;
use App\Http\Requests\Api\V1\Auth\RegisterRequest;
use App\Http\Requests\Api\V1\Auth\ResetPasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdatePasswordRequest;
use App\Http\Requests\Api\V1\Auth\UpdateProfileRequest;
use App\Models\Gym;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function registrationGyms(): JsonResponse
    {
        $gyms = Gym::query()
            ->where('status', GymStatus::Active)
            ->orderBy('name')
            ->get(['id', 'name', 'address']);

        return $this->successResponse($gyms);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $payload = $this->authService->register($request->validated());

        return $this->successResponse(
            $payload,
            'Cadastro enviado. Aguarde a aprovação da academia.',
            201,
        );
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $payload = $this->authService->login($request->validated());
        } catch (ValidationException $exception) {
            return $this->errorResponse(
                'Não foi possível realizar a operação',
                422,
                $exception->errors(),
            );
        }

        return $this->successResponse($payload, 'Login realizado com sucesso');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->successResponse(message: 'Logout realizado com sucesso');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            $this->authService->me($request->user()),
        );
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        return $this->successResponse(
            $this->authService->updateProfile($request->user(), $request->validated()),
            'Perfil atualizado com sucesso',
        );
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $this->authService->updatePassword($request->user(), $request->validated());

        return $this->successResponse(message: 'Senha atualizada com sucesso');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->sendPasswordResetLink($request->validated('email'));

        if ($status !== Password::RESET_LINK_SENT) {
            return $this->errorResponse(__($status), 422);
        }

        return $this->successResponse(message: 'Link de recuperação enviado com sucesso');
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = $this->authService->resetPassword($request->validated());

        if ($status !== Password::PASSWORD_RESET) {
            return $this->errorResponse(__($status), 422);
        }

        return $this->successResponse(message: 'Senha redefinida com sucesso');
    }
}
