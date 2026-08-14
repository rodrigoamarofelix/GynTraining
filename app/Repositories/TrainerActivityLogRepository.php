<?php

namespace App\Repositories;

use App\Models\TrainerActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TrainerActivityLogRepository
{
    public function paginateForTrainer(int $trainerId, int $perPage = 20): LengthAwarePaginator
    {
        return TrainerActivityLog::query()
            ->with(['performer'])
            ->where('trainer_id', $trainerId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): TrainerActivityLog
    {
        return TrainerActivityLog::query()->create($data);
    }
}
