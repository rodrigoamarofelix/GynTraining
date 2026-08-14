<?php

namespace App\Enums;

enum ProgressPhotoVisibility: string
{
    case Private = 'private';
    case Trainer = 'trainer';
    case Gym = 'gym';
}
