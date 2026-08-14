<?php

namespace App\Repositories;

use App\Models\ProgressPhoto;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ProgressPhotoRepository
{
    public function paginate(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return ProgressPhoto::query()
            ->with('student.user')
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('student_id', $id))
            ->when($filters['category'] ?? null, fn ($q, $category) => $q->where('category', $category))
            ->latest('taken_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?ProgressPhoto
    {
        return ProgressPhoto::query()->with('student.user')->find($id);
    }

    public function create(array $data): ProgressPhoto
    {
        return ProgressPhoto::query()->create($data);
    }

    public function update(ProgressPhoto $photo, array $data): ProgressPhoto
    {
        $photo->update($data);

        return $photo->fresh(['student.user']);
    }

    public function softDelete(ProgressPhoto $photo): void
    {
        $photo->delete();
    }
}
