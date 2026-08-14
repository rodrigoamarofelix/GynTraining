<?php

namespace App\Models;

use App\Enums\ExerciseCategoryActivityAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseCategoryActivityLog extends BaseModel
{
    protected $fillable = [
        'exercise_category_id',
        'performed_by',
        'action',
        'changes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'action' => ExerciseCategoryActivityAction::class,
            'changes' => 'array',
        ];
    }

    public function exerciseCategory(): BelongsTo
    {
        return $this->belongsTo(ExerciseCategory::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
