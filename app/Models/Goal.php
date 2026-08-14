<?php

namespace App\Models;

use App\Enums\GoalStatus;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Goal extends BaseModel
{
    protected $fillable = [
        'student_id',
        'exercise_id',
        'name',
        'description',
        'target',
        'current_value',
        'unit',
        'start_date',
        'target_date',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'target' => 'decimal:2',
            'current_value' => 'decimal:2',
            'start_date' => 'date',
            'target_date' => 'date',
            'status' => GoalStatus::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function progressPercentage(): float
    {
        if ((float) $this->target <= 0) {
            return 0;
        }

        return min(100, round(((float) $this->current_value / (float) $this->target) * 100, 2));
    }
}
