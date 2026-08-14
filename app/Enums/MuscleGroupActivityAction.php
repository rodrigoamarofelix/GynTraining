<?php

namespace App\Enums;

enum MuscleGroupActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
}
