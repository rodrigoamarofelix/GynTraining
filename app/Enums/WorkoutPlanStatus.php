<?php

namespace App\Enums;

enum WorkoutPlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';
    case Completed = 'completed';
}
