<?php

namespace App\Repositories;

use App\Models\GymActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class GymActivityLogRepository
{
    public function paginateForGym(int $gymId, int $perPage = 20): LengthAwarePaginator
    {
        return GymActivityLog::query()
            ->with('performer')
            ->where('gym_id', $gymId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): GymActivityLog
    {
        return GymActivityLog::query()->create($data);
    }
}
