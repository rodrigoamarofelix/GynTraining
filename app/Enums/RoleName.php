<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case GymAdmin = 'gym_admin';
    case Trainer = 'trainer';
    case Student = 'student';
}
