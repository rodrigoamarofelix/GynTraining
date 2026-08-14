<?php

namespace App\Enums;

enum ProfileStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';
}
