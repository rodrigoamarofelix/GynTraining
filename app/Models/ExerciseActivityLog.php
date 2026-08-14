<?php

namespace App\Models;

use App\Enums\ExerciseActivityAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseActivityLog extends BaseModel
{
    protected $fillable = [
        'exercise_id',
        'performed_by',
        'action',
        'changes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'action' => ExerciseActivityAction::class,
            'changes' => 'array',
        ];
    }

    public function exercise(): BelongsTo
    {
        return $this->belongsTo(Exercise::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
