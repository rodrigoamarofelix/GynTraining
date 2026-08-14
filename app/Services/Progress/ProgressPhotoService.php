<?php

namespace App\Services\Progress;

use App\Models\ProgressPhoto;
use App\Repositories\ProgressPhotoRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProgressPhotoService
{
    public function __construct(
        private readonly ProgressPhotoRepository $repository,
        private readonly StudentProgressAccess $access,
    ) {}

    public function list(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginate($filters, $perPage);
    }

    public function find(int $id): ?ProgressPhoto
    {
        return $this->repository->findById($id);
    }

    public function create(array $data, UploadedFile $photo): ProgressPhoto
    {
        $path = $photo->store(
            'progress-photos/'.$data['student_id'],
            'public',
        );

        $data['photo_path'] = $path;

        return $this->repository->create($data);
    }

    public function update(ProgressPhoto $photo, array $data, ?UploadedFile $newPhoto = null): ProgressPhoto
    {
        if ($newPhoto) {
            Storage::disk('public')->delete($photo->photo_path);
            $data['photo_path'] = $newPhoto->store(
                'progress-photos/'.$photo->student_id,
                'public',
            );
        }

        return $this->repository->update($photo, $data);
    }

    public function delete(ProgressPhoto $photo): void
    {
        DB::transaction(function () use ($photo) {
            Storage::disk('public')->delete($photo->photo_path);
            $this->repository->softDelete($photo);
        });
    }

    public function filtersForUser($user): array
    {
        return $this->access->filtersForUser($user);
    }
}
