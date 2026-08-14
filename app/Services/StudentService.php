<?php

namespace App\Services;

use App\Enums\ProfileStatus;
use App\Enums\RoleName;
use App\Support\ManagedGymScope;
use App\Enums\StudentActivityAction;
use App\Enums\UserStatus;
use App\Models\Student;
use App\Models\User;
use App\Repositories\StudentActivityLogRepository;
use App\Repositories\StudentRepository;
use App\Repositories\UserRepository;
use App\Services\Notification\AppNotificationService;
use App\Services\Student\StudentActivityLogger;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class StudentService
{
    public function __construct(
        private readonly StudentRepository $studentRepository,
        private readonly UserRepository $userRepository,
        private readonly AppNotificationService $notificationService,
        private readonly StudentActivityLogger $activityLogger,
        private readonly StudentActivityLogRepository $activityLogRepository,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->studentRepository->paginate($filters, $perPage);
    }

    public function find(int $id, bool $withTrashed = false): ?Student
    {
        return $this->studentRepository->findById($id, $withTrashed);
    }

    public function activityLogs(int $studentId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->activityLogRepository->paginateForStudent($studentId, $perPage);
    }

    public function create(array $data, ?User $performer = null): Student
    {
        return DB::transaction(function () use ($data, $performer) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'status' => UserStatus::Active,
            ]);

            $this->userRepository->assignRole($user, RoleName::Student);

            $student = $this->studentRepository->create([
                'user_id' => $user->id,
                'gym_id' => $data['gym_id'],
                'trainer_id' => $data['trainer_id'] ?? null,
                'birth_date' => $data['birth_date'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? ProfileStatus::Active->value,
            ])->load(['user', 'gym', 'trainer.user']);

            $this->activityLogger->log(
                $student,
                $performer,
                StudentActivityAction::Created,
                $this->activityLogger->diff([], $this->activityLogger->snapshot($student), $data),
            );

            return $student;
        });
    }

    public function registerPublic(array $data): Student
    {
        return DB::transaction(function () use ($data) {
            $user = $this->userRepository->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'phone' => $data['phone'] ?? null,
                'status' => UserStatus::Inactive,
            ]);

            $this->userRepository->assignRole($user, RoleName::Student);

            $student = $this->studentRepository->create([
                'user_id' => $user->id,
                'gym_id' => $data['gym_id'],
                'status' => ProfileStatus::Pending,
            ])->load(['user', 'gym']);

            $this->activityLogger->log($student, null, StudentActivityAction::Registered);

            return $student;
        });
    }

    public function update(Student $student, array $data, ?User $performer = null): Student
    {
        return DB::transaction(function () use ($student, $data, $performer) {
            $before = $this->activityLogger->snapshot($student);
            $wasPending = $student->status === ProfileStatus::Pending;
            $newStatus = $data['status'] ?? null;

            $userData = array_filter([
                'name' => $data['name'] ?? null,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'password' => $data['password'] ?? null,
            ], fn ($value) => $value !== null);

            if ($userData !== []) {
                $student->user->update($userData);
            }

            $studentData = [];

            foreach (['gym_id', 'trainer_id', 'birth_date', 'notes', 'status'] as $field) {
                if (array_key_exists($field, $data)) {
                    $studentData[$field] = $data[$field];
                }
            }

            $student = $this->studentRepository->update($student, $studentData);
            $student->load(['user', 'gym', 'trainer.user']);

            if ($wasPending && $newStatus === ProfileStatus::Active->value) {
                $student->user->update(['status' => UserStatus::Active]);
                $this->notificationService->markPendingStudentRegistrationResolved($student->id);
            }

            if ($newStatus === ProfileStatus::Active->value && $student->user->status !== UserStatus::Active) {
                $student->user->update(['status' => UserStatus::Active]);
            }

            if ($newStatus === ProfileStatus::Inactive->value) {
                $student->user->update(['status' => UserStatus::Inactive]);
            }

            $after = $this->activityLogger->snapshot($student->fresh(['user', 'gym', 'trainer.user']));
            $changes = $this->activityLogger->diff($before, $after, $data);

            if ($wasPending && $newStatus === ProfileStatus::Active->value) {
                $this->activityLogger->log($student, $performer, StudentActivityAction::Approved, $changes);
            } elseif ($changes !== []) {
                $this->activityLogger->log($student, $performer, StudentActivityAction::Updated, $changes);
            }

            return $student;
        });
    }

    public function delete(Student $student, ?User $performer = null): void
    {
        DB::transaction(function () use ($student, $performer) {
            $student->update(['gym_cascade_at' => null]);

            $this->activityLogger->log($student, $performer, StudentActivityAction::Deleted);

            $this->studentRepository->delete($student);
            $student->user->delete();
        });
    }

    public function restore(int $studentId, User $performer): Student
    {
        return DB::transaction(function () use ($studentId, $performer) {
            $student = $this->studentRepository->findById($studentId, true);

            if (! $student || ! $student->trashed()) {
                abort(404, 'Aluno não encontrado ou não está inativo.');
            }

            $student = $this->studentRepository->restore($student);
            $student->update([
                'status' => ProfileStatus::Active,
                'gym_cascade_at' => null,
            ]);

            $user = $student->user()->withTrashed()->first();

            if ($user) {
                $user->restore();
                $user->update(['status' => UserStatus::Active]);
            }

            $student = $student->fresh(['user', 'gym', 'trainer.user']);

            $this->activityLogger->log($student, $performer, StudentActivityAction::Restored);

            return $student;
        });
    }

    public function filtersForUser(User $user): array
    {
        if ($user->hasRole(RoleName::Trainer) && $user->trainer) {
            return [
                'gym_id' => $user->trainer->gym_id,
                'trainer_scope_id' => $user->trainer->id,
                'scope' => 'active',
            ];
        }

        if ($user->hasRole(RoleName::Student) && $user->student) {
            return ['gym_id' => $user->student->gym_id];
        }

        return ManagedGymScope::filtersFor($user);
    }
}
