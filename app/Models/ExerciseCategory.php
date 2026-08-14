<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;

class ExerciseCategory extends BaseModel
{
    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    public function exercises(): HasMany
    {
        return $this->hasMany(Exercise::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ExerciseCategoryActivityLog::class);
    }
}
