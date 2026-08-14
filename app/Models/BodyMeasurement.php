<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BodyMeasurement extends BaseModel
{
    protected $fillable = [
        'student_id',
        'measured_at',
        'weight',
        'height',
        'bmi',
        'arm',
        'forearm',
        'chest',
        'waist',
        'abdomen',
        'hip',
        'thigh',
        'calf',
        'body_fat_percentage',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'measured_at' => 'date',
            'weight' => 'decimal:2',
            'height' => 'decimal:2',
            'bmi' => 'decimal:2',
            'arm' => 'decimal:2',
            'forearm' => 'decimal:2',
            'chest' => 'decimal:2',
            'waist' => 'decimal:2',
            'abdomen' => 'decimal:2',
            'hip' => 'decimal:2',
            'thigh' => 'decimal:2',
            'calf' => 'decimal:2',
            'body_fat_percentage' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
