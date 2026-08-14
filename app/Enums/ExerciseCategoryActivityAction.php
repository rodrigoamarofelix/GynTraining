<?php

namespace App\Enums;

enum ExerciseCategoryActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
