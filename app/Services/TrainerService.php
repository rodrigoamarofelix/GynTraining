<?php

namespace App\Services;

use App\Enums\ProfileStatus;
use App\Enums\RoleName;
use App\Support\ManagedGymScope;
use App\Enums\TrainerActivityAction;
use App\Enums\UserStatus;
use App\Models\Trainer;
use App\Models\User;
use App\Repositories\TrainerActivityLogRepository;
use App\Repositories\TrainerRepository;
use App\Repositories\UserRepository;
use App\Services\Trainer\TrainerActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class TrainerService
{
    public function __construct(
        private readonly TrainerRepository $trainerRepository,
        private readonly UserRepository $userRepository,
        private readonly TrainerActivityLogger $activityLogger,
        private readonly TrainerActivityLogRepository $activityLogRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->trainerRepository->paginate($filters, $perPage);
    }

    public function find(int $id, bool $withTrashed = false): ?Trainer
    {
        return $this->trainerRepository->findById($id, $withTrashed);
    }

    public function activityLogs(int $trainerId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activityLogRepository->paginateForTrainer($trainerId, $perPage);
    }

    public function create(array $data, ?User $performer = null): Trainer
    {
        return DB::transaction(function () use ($data, $performer) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'status' => UserStatus::Active,
            ]);

            $this->userRepository->assignRole($user, RoleName::Trainer);

            $trainer = $this->trainerRepository->create([
                'user_id' => $user->id,
                'gym_id' => $data['gym_id'],
                'bio' => $data['bio'] ?? null,
                'specialty' => $data['specialty'] ?? null,
                'status' => $data['status'] ?? ProfileStatus::Active->value,
            ])->load(['user', 'gym']);

            $this->activityLogger->log(
                $trainer,
                $performer,
                TrainerActivityAction::Created,
                $this->activityLogger->diff([], $this->activityLogger->snapshot($trainer), $data),
            );

            return $trainer;
        });
    }

    public function update(Trainer $trainer, array $data, ?User $performer = null): Trainer
    {
        return DB::transaction(function () use ($trainer, $data, $performer) {
            $before = $this->activityLogger->snapshot($trainer);
            $newStatus = $data['status'] ?? null;

            $userData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? null,
            ], fn ($value) => $value !== null);

            if ($userData !== []) {
                $trainer->user->update($userData);
            }

            $trainerData = [];

            foreach (['gym_id', 'bio', 'specialty', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $trainerData[$field] = $data[$field];
                }
            }

            $trainer = $this->trainerRepository->update($trainer, $trainerData);
            $trainer->load(['user', 'gym']);

            if ($newStatus === ProfileStatus::Active->value) {
                $trainer->user->update(['status' => UserStatus::Active]);
            }

            if ($newStatus === ProfileStatus::Inactive->value) {
                $trainer->user->update(['status' => UserStatus::Inactive]);
            }

            $after = $this->activityLogger->snapshot($trainer->fresh(['user', 'gym']));
            $changes = $this->activityLogger->diff($before, $after, $data);

            if ($changes !== []) {
                $this->activityLogger->log($trainer, $performer, TrainerActivityAction::Updated, $changes);
            }

            return $trainer;
        });
    }

    public function delete(Trainer $trainer, ?User $performer = null): void
    {
        DB::transaction(function () use ($trainer, $performer) {
            $trainer->update(['gym_cascade_at' => null]);

            $this->activityLogger->log($trainer, $performer, TrainerActivityAction::Deleted);

            $this->trainerRepository->delete($trainer);
            $trainer->user->delete();
        });
    }

    public function restore(int $trainerId, User $performer): Trainer
    {
        return DB::transaction(function () use ($trainerId, $performer) {
            $trainer = $this->trainerRepository->findById($trainerId, true);

            if (! $trainer || ! $trainer->trashed()) {
                abort(404, 'Professor não encontrado ou não está inativo.');
            }

            $trainer = $this->trainerRepository->restore($trainer);
            $trainer->update([
                'status' => ProfileStatus::Active,
                'gym_cascade_at' => null,
            ]);

            $user = $trainer->user()->withTrashed()->first();

            if ($user) {
                $user->restore();
                $user->update(['status' => UserStatus::Active]);
            }

            $trainer = $trainer->fresh(['user', 'gym']);

            $this->activityLogger->log($trainer, $performer, TrainerActivityAction::Restored);

            return $trainer;
        });
    }

    public function filtersForUser(User $user): array
    {
        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            return ['gym_id' => $user->trainer->gym_id];
        }

        return ManagedGymScope::filtersFor($user);
    }
}
