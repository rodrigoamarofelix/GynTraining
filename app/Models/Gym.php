<?php

namespace App\Models;

use App\Enums\GymStatus;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gym extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'address',
        'phone',
        'email',
        'logo',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => GymStatus::class,
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function trainers(): HasMany
    {
        return $this->hasMany(Trainer::class);
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(GymActivityLog::class);
    }
}
