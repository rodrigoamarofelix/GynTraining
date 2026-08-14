<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutDay extends BaseModel
{
    protected $fillable = [
        'workout_plan_id',
        'name',
        'description',
        'order',
    ];

    public function workoutPlan(): BelongsTo
    {
        return $this->belongsTo(WorkoutPlan::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(WorkoutExercise::class)->orderBy('order');
    }
}
