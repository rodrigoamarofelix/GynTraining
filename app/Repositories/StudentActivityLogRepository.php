<?php

namespace App\Repositories;

use App\Models\Student;
use App\Models\StudentActivityLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class StudentActivityLogRepository
{
    public function paginateForStudent(int $studentId, int $perPage = 20): LengthAwarePaginator
    {
        return StudentActivityLog::query()
            ->with(['performer'])
            ->where('student_id', $studentId)
            ->latest()
            ->paginate($perPage);
    }

    public function create(array $data): StudentActivityLog
    {
        return StudentActivityLog::query()->create($data);
    }

    public function latestForStudent(int $studentId, int $limit = 20): Collection
    {
        return StudentActivityLog::query()
            ->with(['performer'])
            ->where('student_id', $studentId)
            ->latest()
            ->limit($limit)
            ->get();
    }
}
