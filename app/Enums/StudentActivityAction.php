<?php

namespace App\Enums;

enum StudentActivityAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';
    case Approved = 'approved';
    case Registered = 'registered';
}
