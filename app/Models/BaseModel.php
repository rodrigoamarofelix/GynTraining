<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Modelo base do GynTraining.
 * Todas as entidades de negócio devem estender esta classe para garantir deleção lógica.
 */
abstract class BaseModel extends Model
{
    use SoftDeletes;
}
