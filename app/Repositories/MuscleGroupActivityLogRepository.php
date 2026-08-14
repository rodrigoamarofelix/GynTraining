<?php

namespace App\Repositories;

use App\Models\MuscleGroupActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MuscleGroupActivityLogRepository
{
    public function paginateForMuscleGroup(int $muscleGroupId, int $perPage = 20): LengthAwarePaginator
    {
        return MuscleGroupActivityLog::query()
            ->with('performer')
            ->where('muscle_group_id', $muscleGroupId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): MuscleGroupActivityLog
    {
        return MuscleGroupActivityLog::query()->create($data);
    }
}
