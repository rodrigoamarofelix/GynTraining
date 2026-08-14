<?php

namespace App\Models;

use App\Enums\ProgressPhotoCategory;
use App\Enums\ProgressPhotoVisibility;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProgressPhoto extends BaseModel
{
    protected $fillable = [
        'student_id',
        'category',
        'photo_path',
        'taken_at',
        'visibility',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'category' => ProgressPhotoCategory::class,
            'visibility' => ProgressPhotoVisibility::class,
            'taken_at' => 'date',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }
}
