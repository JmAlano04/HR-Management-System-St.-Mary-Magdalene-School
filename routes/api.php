<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ZKTecoController;

Route::post(
    '/zkteco/receive',
    [ZKTecoController::class, 'receiveFromPython']
);

Route::get(
    '/zkteco/test',
    [ZKTecoController::class, 'test']
);
