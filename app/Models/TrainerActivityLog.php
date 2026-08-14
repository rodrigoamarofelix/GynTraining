<?php

namespace App\Models;

use App\Enums\TrainerActivityAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrainerActivityLog extends BaseModel
{
    protected $fillable = [
        'trainer_id',
        'performed_by',
        'action',
        'changes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'action' => TrainerActivityAction::class,
            'changes' => 'array',
        ];
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
