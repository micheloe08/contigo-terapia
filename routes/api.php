<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

// Rutas públicas
Route::prefix('v1')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('register/patient', [AuthController::class, 'registerPatient']);
        Route::post('register/doctor',  [AuthController::class, 'registerDoctor']);
        Route::post('login',            [AuthController::class, 'login']);
    });

    // Rutas protegidas
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

    });
});
