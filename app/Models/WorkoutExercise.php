<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutExercise extends BaseModel
{
    protected $fillable = [
        'workout_day_id',
        'exercise_id',
        'order',
        'notes',
        'execution_time',
        'rest_time',
    ];

    public function workoutDay(): BelongsTo
    {
        return $this->belongsTo(WorkoutDay::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function sets(): HasMany
    {
        return $this->hasMany(WorkoutSet::class)->orderBy('set_number');
    }
}
