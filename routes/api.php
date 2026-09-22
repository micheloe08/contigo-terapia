<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Rutas públicas — auth
    Route::prefix('auth')->group(function () {
        Route::post('register/patient', [AuthController::class, 'registerPatient']);
        Route::post('register/doctor',  [AuthController::class, 'registerDoctor']);
        Route::post('login',            [AuthController::class, 'login']);
    });

    // Rutas protegidas — cualquier usuario autenticado
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // Rutas de admin
        Route::middleware('role:admin')->prefix('admin')->group(function () {
            // Aquí irán los endpoints del panel de admin
        });

        // Rutas de admin y operator
        Route::middleware('role:admin,operator')->prefix('management')->group(function () {
            // Aquí irán endpoints de gestión compartidos
        });

        // Rutas de supervisor de terapeutas
        Route::middleware('role:admin,supervisor_doctor')->prefix('supervisor')->group(function () {
            // Aquí irán los endpoints de aprobación de terapeutas
        });

        // Rutas de doctor
        Route::middleware('role:doctor')->prefix('doctor')->group(function () {
            // Aquí irán los endpoints del dashboard de doctor
        });

        // Rutas de paciente
        Route::middleware('role:patient')->prefix('patient')->group(function () {
            // Aquí irán los endpoints del dashboard de paciente
        });
    });
});
