<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSessionExercise extends BaseModel
{
    protected $fillable = [
        'workout_session_id',
        'exercise_id',
        'workout_exercise_id',
        'order',
        'started_at',
        'finished_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function workoutExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutExercise::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class)->orderBy('set_number');
    }
}
