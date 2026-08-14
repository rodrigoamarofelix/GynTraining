<?php

namespace App\Models;

use App\Enums\WorkoutPlanStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkoutPlan extends BaseModel
{
    protected $fillable = [
        'student_id',
        'trainer_id',
        'name',
        'description',
        'start_date',
        'end_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'status' => WorkoutPlanStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function days(): HasMany
    {
        return $this->hasMany(WorkoutDay::class)->orderBy('order');
    }
}
