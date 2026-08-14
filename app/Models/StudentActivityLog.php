<?php

namespace App\Models;

use App\Enums\StudentActivityAction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentActivityLog extends BaseModel
{
    protected $fillable = [
        'student_id',
        'performed_by',
        'action',
        'changes',
        'summary',
    ];

    protected function casts(): array
    {
        return [
            'action' => StudentActivityAction::class,
            'changes' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
