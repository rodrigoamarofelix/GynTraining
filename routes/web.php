<?php

use App\Http\Controllers\Web\SpaController;
use Illuminate\Support\Facades\Route;

Route::get('/', SpaController::class);

Route::get('/{path}', SpaController::class)
    ->where('path', '^(?!docs|up).*$');
