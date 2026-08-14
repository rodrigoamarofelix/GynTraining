<?php

namespace App\Models;

use App\Enums\GymActivityAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GymActivityLog extends BaseModel
{
    protected $fillable = [
        'gym_id',
        'performed_by',
        'action',
        'changes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'action' => GymActivityAction::class,
            'changes' => 'array',
        ];
    }

    public function gym(): BelongsTo
    {
        return $this->belongsTo(Gym::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
