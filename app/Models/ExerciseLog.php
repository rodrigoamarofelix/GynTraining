<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseLog extends BaseModel
{
    protected $fillable = [
        'workout_session_id',
        'workout_session_exercise_id',
        'exercise_id',
        'student_id',
        'set_number',
        'repetitions',
        'load',
        'rest_time',
        'duration',
        'notes',
        'logged_at',
    ];

    protected function casts(): array
    {
        return [
            'load' => 'decimal:2',
            'logged_at' => 'datetime',
        ];
    }

    public function workoutSession(): BelongsTo
    {
        return $this->belongsTo(WorkoutSession::class);
    }

    public function workoutSessionExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutSessionExercise::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
