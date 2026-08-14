<?php

namespace App\Services\Progress;

use App\Enums\GoalStatus;
use App\Models\Goal;
use App\Repositories\GoalRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GoalService
{
    public function __construct(
        private readonly GoalRepository $repository,
        private readonly StudentProgressAccess $access,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): ?Goal
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): Goal
    {
        $data['status'] = $data['status'] ?? GoalStatus::Active->value;
        $data['current_value'] = $data['current_value'] ?? 0;

        return $this->repository->create($data);
    }

    public function update(Goal $goal, array $data): Goal
    {
        return $this->repository->update($goal, $data);
    }

    public function delete(Goal $goal): void
    {
        $this->repository->softDelete($goal);
    }

    public function filtersForUser($user): array
    {
        return $this->access->filtersForUser($user);
    }
}
