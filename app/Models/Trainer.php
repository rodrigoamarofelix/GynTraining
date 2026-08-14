<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trainer extends BaseModel
{
    protected $fillable = [
        'user_id',
        'gym_id',
        'bio',
        'specialty',
        'status',
        'gym_cascade_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ProfileStatus::class,
            'gym_cascade_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(TrainerActivityLog::class);
    }
}
