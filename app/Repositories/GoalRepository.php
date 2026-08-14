<?php

namespace App\Repositories;

use App\Models\Goal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GoalRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return Goal::query()
            ->with(['student.user', 'exercise'])
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->when($filters['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Goal
    {
        return Goal::query()->with(['student.user', 'exercise'])->find($id);
    }

    public function create(array $data): Goal
    {
        return Goal::query()->create($data);
    }

    public function update(Goal $goal, array $data): Goal
    {
        $goal->update($data);

        return $goal->fresh(['student.user', 'exercise']);
    }

    public function softDelete(Goal $goal): void
    {
        $goal->delete();
    }
}
