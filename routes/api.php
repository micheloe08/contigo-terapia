<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Supervisor\DoctorApprovalController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Endpoint público — catálogos para formularios
    Route::get('catalogs', [CatalogController::class, 'public']);

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
            Route::get('catalogs/{type}',        [CatalogController::class, 'index']);
            Route::post('catalogs/{type}',       [CatalogController::class, 'store']);
            Route::put('catalogs/{type}/{id}',   [CatalogController::class, 'update']);
            Route::delete('catalogs/{type}/{id}',[CatalogController::class, 'destroy']);
        });

        // Rutas de admin y operator
        Route::middleware('role:admin,operator')->prefix('management')->group(function () {
            // Aquí irán endpoints de gestión compartidos
        });

        // Rutas de supervisor de terapeutas
        Route::middleware('role:admin,supervisor_doctor')->prefix('supervisor')->group(function () {
            Route::get('stats',                          [DoctorApprovalController::class, 'stats']);
            Route::get('doctors',                        [DoctorApprovalController::class, 'index']);
            Route::get('doctors/{doctor}',               [DoctorApprovalController::class, 'show']);
            Route::post('doctors/{doctor}/approve',      [DoctorApprovalController::class, 'approve']);
            Route::post('doctors/{doctor}/reject',       [DoctorApprovalController::class, 'reject']);
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
