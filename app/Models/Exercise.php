<?php

namespace App\Models;

use App\Enums\ExerciseDifficulty;
use App\Enums\ExerciseStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exercise extends BaseModel
{
    protected $fillable = [
        'name',
        'description',
        'instructions',
        'exercise_category_id',
        'muscle_group_id',
        'gym_id',
        'equipment',
        'difficulty',
        'video_url',
        'image_url',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => ExerciseDifficulty::class,
            'status' => ExerciseStatus::class,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExerciseCategory::class, 'exercise_category_id');
    }

    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ExerciseActivityLog::class);
    }
}
