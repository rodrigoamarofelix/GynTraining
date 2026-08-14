<?php

namespace App\Models;

use App\Enums\MuscleGroupActivityAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MuscleGroupActivityLog extends BaseModel
{
    protected $fillable = [
        'muscle_group_id',
        'performed_by',
        'action',
        'changes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'action' => MuscleGroupActivityAction::class,
            'changes' => 'array',
        ];
    }

    public function muscleGroup(): BelongsTo
    {
        return $this->belongsTo(MuscleGroup::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
