<?php

namespace App\Services\Progress;

use App\Models\BodyMeasurement;
use App\Repositories\BodyMeasurementRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BodyMeasurementService
{
    public function __construct(
        private readonly BodyMeasurementRepository $repository,
        private readonly StudentProgressAccess $access,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): ?BodyMeasurement
    {
        return $this->repository->findById($id);
    }

    public function create(array $data): BodyMeasurement
    {
        $data['bmi'] = $this->calculateBmi($data['weight'] ?? null, $data['height'] ?? null);

        return $this->repository->create($data);
    }

    public function update(BodyMeasurement $measurement, array $data): BodyMeasurement
    {
        $weight = $data['weight'] ?? $measurement->weight;
        $height = $data['height'] ?? $measurement->height;
        $data['bmi'] = $this->calculateBmi($weight, $height);

        return $this->repository->update($measurement, $data);
    }

    public function delete(BodyMeasurement $measurement): void
    {
        $this->repository->softDelete($measurement);
    }

    public function filtersForUser($user): array
    {
        return $this->access->filtersForUser($user);
    }

    private function calculateBmi(mixed $weight, mixed $height): ?float
    {
        if ($weight === null || $height === null || (float) $height <= 0) {
            return null;
        }

        $heightMeters = (float) $height / 100;

        return round((float) $weight / ($heightMeters * $heightMeters), 2);
    }
}
