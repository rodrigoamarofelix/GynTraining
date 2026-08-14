<?php

namespace App\Services\Auth;

use App\Enums\ProfileStatus;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Auth\GymLoginGuard;
use App\Services\Notification\AppNotificationService;
use App\Services\StudentService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly StudentService $studentService,
        private readonly AppNotificationService $notificationService,
        private readonly GymLoginGuard $gymLoginGuard,
    ) {}

    public function register(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $student = $this->studentService->registerPublic($data);

            $this->notificationService->notifyPendingStudentRegistration($student);

            $user = $student->user->load('roles');

            return [
                'user' => new UserResource($user),
                'token' => null,
                'pending_approval' => true,
            ];
        });
    }

    public function login(array $credentials): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Credenciais inválidas.'],
            ]);
        }

        if (! $user->isActive()) {
            $user->loadMissing('student');

            if ($user->student?->status === ProfileStatus::Pending) {
                throw ValidationException::withMessages([
                    'email' => ['Seu cadastro está aguardando aprovação da academia.'],
                ]);
            }

            throw ValidationException::withMessages([
                'email' => ['Usuário inativo ou suspenso.'],
            ]);
        }

        $this->gymLoginGuard->ensureUserCanLogin($user);

        $user->load('roles');
        $user->load('gyms');
        $token = $user->createToken('auth-token')->plainTextToken;

        return [
            'user' => new UserResource($user),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()?->delete();
    }

    public function me(User $user): UserResource
    {
        return new UserResource($user->load(['roles', 'gyms']));
    }

    public function updateProfile(User $user, array $data): UserResource
    {
        $payload = array_filter([
            'name' => $data['name'] ?? null,
            'phone' => $data['phone'] ?? null,
        ], fn ($value) => $value !== null);

        if ($payload !== []) {
            $user->update($payload);
        }

        return new UserResource($user->fresh(['roles', 'gyms']));
    }

    public function updatePassword(User $user, array $data): void
    {
        if (! Hash::check($data['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Senha atual incorreta.'],
            ]);
        }

        $user->update(['password' => $data['password']]);
    }

    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    public function resetPassword(array $data): string
    {
        return Password::reset(
            $data,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            },
        );
    }
}
