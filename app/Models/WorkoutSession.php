<?php

namespace App\Models;

use App\Enums\WorkoutSessionStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutSession extends BaseModel
{
    protected $fillable = [
        'student_id',
        'workout_plan_id',
        'workout_day_id',
        'started_at',
        'finished_at',
        'duration_seconds',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'duration_seconds' => 'integer',
            'status' => WorkoutSessionStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function workoutDay(): BelongsTo
    {
        return $this->belongsTo(WorkoutDay::class);
    }

    public function sessionExercises(): HasMany
    {
        return $this->hasMany(WorkoutSessionExercise::class)->orderBy('order');
    }

    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class)->orderBy('logged_at');
    }

    public function isInProgress(): bool
    {
        return $this->status === WorkoutSessionStatus::InProgress;
    }
}
