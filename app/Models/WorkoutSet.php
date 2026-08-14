<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkoutSet extends BaseModel
{
    protected $fillable = [
        'workout_exercise_id',
        'set_number',
        'repetitions',
        'load',
        'rest_time',
        'duration',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'load' => 'decimal:2',
        ];
    }

    public function workoutExercise(): BelongsTo
    {
        return $this->belongsTo(WorkoutExercise::class);
    }
}
