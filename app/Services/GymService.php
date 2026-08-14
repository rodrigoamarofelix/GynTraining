<?php

namespace App\Services;

use App\Enums\GymActivityAction;
use App\Enums\GymStatus;
use App\Enums\ProfileStatus;
use App\Enums\StudentActivityAction;
use App\Enums\TrainerActivityAction;
use App\Enums\UserStatus;
use App\Models\Gym;
use App\Models\Student;
use App\Models\Trainer;
use App\Models\User;
use App\Repositories\GymActivityLogRepository;
use App\Repositories\GymRepository;
use App\Repositories\StudentRepository;
use App\Repositories\TrainerRepository;
use App\Services\Gym\GymActivityLogger;
use App\Services\Student\StudentActivityLogger;
use App\Services\Trainer\TrainerActivityLogger;
use App\Support\ManagedGymScope;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GymService
{
    public function __construct(
        private readonly GymRepository $gymRepository,
        private readonly GymActivityLogger $activityLogger,
        private readonly GymActivityLogRepository $activityLogRepository,
        private readonly StudentActivityLogger $studentActivityLogger,
        private readonly TrainerActivityLogger $trainerActivityLogger,
        private readonly StudentRepository $studentRepository,
        private readonly TrainerRepository $trainerRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->gymRepository->paginate($filters, $perPage);
    }

    public function filtersForUser(User $user): array
    {
        return ManagedGymScope::filtersFor($user);
    }

    public function find(int $id, bool $withTrashed = false): ?Gym
    {
        return $this->gymRepository->findById($id, $withTrashed);
    }

    public function activityLogs(int $gymId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activityLogRepository->paginateForGym($gymId, $perPage);
    }

    public function members(int $gymId, string $scope = 'active'): array
    {
        $filters = [
            'gym_id' => $gymId,
            'scope' => $scope,
        ];

        return [
            'trainers' => $this->trainerRepository->paginate($filters, 100)->items(),
            'students' => $this->studentRepository->paginate($filters, 100)->items(),
        ];
    }

    public function create(array $data, ?User $performer = null): Gym
    {
        return DB::transaction(function () use ($data, $performer) {
            $data['slug'] = $this->makeUniqueSlug($data['name']);
            $data['status'] = $data['status'] ?? GymStatus::Active->value;

            $gym = $this->gymRepository->create($data);

            if ($performer) {
                $gym->users()->syncWithoutDetaching([$performer->id]);
            }

            $this->activityLogger->log(
                $gym,
                $performer,
                GymActivityAction::Created,
                $this->activityLogger->diff([], $this->activityLogger->snapshot($gym), $data),
            );

            return $gym->fresh();
        });
    }

    public function update(Gym $gym, array $data, ?User $performer = null): Gym
    {
        return DB::transaction(function () use ($gym, $data, $performer) {
            $before = $this->activityLogger->snapshot($gym);

            if (isset($data['name']) && $data['name'] !== $gym->name) {
                $data['slug'] = $this->makeUniqueSlug($data['name'], $gym->id);
            }

            $gym = $this->gymRepository->update($gym, $data);
            $after = $this->activityLogger->snapshot($gym->fresh());
            $changes = $this->activityLogger->diff($before, $after, $data);

            if ($changes !== []) {
                $this->activityLogger->log($gym, $performer, GymActivityAction::Updated, $changes);
            }

            return $gym;
        });
    }

    public function delete(Gym $gym, ?User $performer = null): void
    {
        DB::transaction(function () use ($gym, $performer) {
            $cascadeAt = now();
            $studentsCount = $this->deactivateStudentsForGymCascade($gym, $performer, $cascadeAt);
            $trainersCount = $this->deactivateTrainersForGymCascade($gym, $performer, $cascadeAt);

            $gym->update(['status' => GymStatus::Inactive]);
            $this->gymRepository->delete($gym);

            $summary = sprintf(
                '%s excluiu a academia (deleção lógica)',
                $performer?->name ?? 'Sistema',
            );

            if ($studentsCount > 0 || $trainersCount > 0) {
                $summary .= sprintf(
                    ' e desativou %d aluno(s) e %d professor(es) em cascata.',
                    $studentsCount,
                    $trainersCount,
                );
            } else {
                $summary .= '.';
            }

            $this->activityLogger->log($gym, $performer, GymActivityAction::Deleted, [], $summary);
        });
    }

    public function restore(int $gymId, User $performer): Gym
    {
        return DB::transaction(function () use ($gymId, $performer) {
            $gym = $this->gymRepository->findById($gymId, true);

            if (! $gym || ! $gym->trashed()) {
                abort(404, 'Academia não encontrada ou não está inativa.');
            }

            $studentsCount = $this->restoreStudentsForGymCascade($gymId, $performer);
            $trainersCount = $this->restoreTrainersForGymCascade($gymId, $performer);

            $gym = $this->gymRepository->restore($gym);
            $gym->update(['status' => GymStatus::Active]);

            $summary = sprintf(
                '%s reativou a academia',
                $performer->name,
            );

            if ($studentsCount > 0 || $trainersCount > 0) {
                $summary .= sprintf(
                    ' e restaurou %d aluno(s) e %d professor(es) em cascata.',
                    $studentsCount,
                    $trainersCount,
                );
            } else {
                $summary .= '.';
            }

            $this->activityLogger->log($gym, $performer, GymActivityAction::Restored, [], $summary);

            return $gym->fresh();
        });
    }

    private function deactivateStudentsForGymCascade(Gym $gym, ?User $performer, $cascadeAt): int
    {
        $count = 0;

        Student::query()
            ->where('gym_id', $gym->id)
            ->whereNull('deleted_at')
            ->with('user')
            ->each(function (Student $student) use ($performer, $cascadeAt, &$count) {
                $student->update([
                    'status' => ProfileStatus::Inactive,
                    'gym_cascade_at' => $cascadeAt,
                ]);

                $this->studentActivityLogger->log(
                    $student,
                    $performer,
                    StudentActivityAction::Deleted,
                    [],
                    ($performer?->name ?? 'Sistema').' desativou o aluno em cascata pela exclusão da academia.',
                );

                $student->delete();

                if ($student->user) {
                    $student->user->update(['status' => UserStatus::Inactive]);
                    $student->user->delete();
                }

                $count++;
            });

        return $count;
    }

    private function deactivateTrainersForGymCascade(Gym $gym, ?User $performer, $cascadeAt): int
    {
        $count = 0;

        Trainer::query()
            ->where('gym_id', $gym->id)
            ->whereNull('deleted_at')
            ->with('user')
            ->each(function (Trainer $trainer) use ($performer, $cascadeAt, &$count) {
                $trainer->update([
                    'status' => ProfileStatus::Inactive,
                    'gym_cascade_at' => $cascadeAt,
                ]);

                $this->trainerActivityLogger->log(
                    $trainer,
                    $performer,
                    TrainerActivityAction::Deleted,
                    [],
                    ($performer?->name ?? 'Sistema').' desativou o professor em cascata pela exclusão da academia.',
                );

                $trainer->delete();

                if ($trainer->user) {
                    $trainer->user->update(['status' => UserStatus::Inactive]);
                    $trainer->user->delete();
                }

                $count++;
            });

        return $count;
    }

    private function restoreStudentsForGymCascade(int $gymId, User $performer): int
    {
        $count = 0;

        Student::withTrashed()
            ->where('gym_id', $gymId)
            ->whereNotNull('gym_cascade_at')
            ->each(function (Student $student) use ($performer, &$count) {
                if (! $student->trashed()) {
                    return;
                }

                $student->restore();
                $student->update([
                    'status' => ProfileStatus::Active,
                    'gym_cascade_at' => null,
                ]);

                $user = $student->user()->withTrashed()->first();

                if ($user) {
                    $user->restore();
                    $user->update(['status' => UserStatus::Active]);
                }

                $student->load(['user', 'gym', 'trainer.user']);

                $this->studentActivityLogger->log(
                    $student,
                    $performer,
                    StudentActivityAction::Restored,
                    [],
                    $performer->name.' reativou o aluno em cascata pela restauração da academia.',
                );

                $count++;
            });

        return $count;
    }

    private function restoreTrainersForGymCascade(int $gymId, User $performer): int
    {
        $count = 0;

        Trainer::withTrashed()
            ->where('gym_id', $gymId)
            ->whereNotNull('gym_cascade_at')
            ->each(function (Trainer $trainer) use ($performer, &$count) {
                if (! $trainer->trashed()) {
                    return;
                }

                $trainer->restore();
                $trainer->update([
                    'status' => ProfileStatus::Active,
                    'gym_cascade_at' => null,
                ]);

                $user = $trainer->user()->withTrashed()->first();

                if ($user) {
                    $user->restore();
                    $user->update(['status' => UserStatus::Active]);
                }

                $trainer->load(['user', 'gym']);

                $this->trainerActivityLogger->log(
                    $trainer,
                    $performer,
                    TrainerActivityAction::Restored,
                    [],
                    $performer->name.' reativou o professor em cascata pela restauração da academia.',
                );

                $count++;
            });

        return $count;
    }

    private function makeUniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($name);
        $slug = $baseSlug;
        $counter = 1;

        while (
            Gym::query()
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $slug = $baseSlug.'-'.$counter;
            $counter++;
        }

        return $slug;
    }
}
