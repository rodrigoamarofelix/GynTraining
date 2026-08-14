<?php

namespace App\Models;

use App\Enums\ProfileStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends BaseModel
{
    protected $fillable = [
        'user_id',
        'gym_id',
        'trainer_id',
        'birth_date',
        'notes',
        'status',
        'gym_cascade_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
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

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function workoutPlans(): HasMany
    {
        return $this->hasMany(WorkoutPlan::class);
    }

    public function workoutSessions(): HasMany
    {
        return $this->hasMany(WorkoutSession::class);
    }

    public function exerciseLogs(): HasMany
    {
        return $this->hasMany(ExerciseLog::class);
    }

    public function bodyMeasurements(): HasMany
    {
        return $this->hasMany(BodyMeasurement::class);
    }

    public function progressPhotos(): HasMany
    {
        return $this->hasMany(ProgressPhoto::class);
    }

    public function goals(): HasMany
    {
        return $this->hasMany(Goal::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(StudentActivityLog::class);
    }
}
