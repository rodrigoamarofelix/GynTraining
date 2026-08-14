<?php

namespace App\Repositories;

use App\Models\BodyMeasurement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BodyMeasurementRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return BodyMeasurement::query()
            ->with('student.user')
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->latest('measured_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?BodyMeasurement
    {
        return BodyMeasurement::query()->with('student.user')->find($id);
    }

    public function latestForStudent(int $studentId): ?BodyMeasurement
    {
        return BodyMeasurement::query()
            ->where('student_id', $studentId)
            ->latest('measured_at')
            ->first();
    }

    public function historyForStudent(int $studentId, int $limit = 12): Collection
    {
        return BodyMeasurement::query()
            ->where('student_id', $studentId)
            ->latest('measured_at')
            ->limit($limit)
            ->get();
    }

    public function create(array $data): BodyMeasurement
    {
        return BodyMeasurement::query()->create($data);
    }

    public function update(BodyMeasurement $measurement, array $data): BodyMeasurement
    {
        $measurement->update($data);

        return $measurement->fresh(['student.user']);
    }

    public function softDelete(BodyMeasurement $measurement): void
    {
        $measurement->delete();
    }
}
