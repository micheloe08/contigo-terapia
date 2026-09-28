<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Admin\CatalogController;
use App\Http\Controllers\Supervisor\DoctorApprovalController;
use App\Http\Controllers\Public\DoctorSearchController;
use App\Http\Controllers\Doctor\ScheduleController;
use App\Http\Controllers\Doctor\AppointmentController as DoctorAppointmentController;
use App\Http\Controllers\Patient\AppointmentController as PatientAppointmentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Endpoint público — catálogos para formularios
    Route::get('catalogs', [CatalogController::class, 'public']);

    // Endpoints públicos — búsqueda de terapeutas
    Route::get('doctors/filters',              [DoctorSearchController::class, 'filters']);
    Route::get('doctors',                      [DoctorSearchController::class, 'index']);
    Route::get('doctors/{doctor}',             [DoctorSearchController::class, 'show']);
    Route::get('doctors/{doctor}/availability',[DoctorSearchController::class, 'availability']);

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
            Route::get('schedules',               [ScheduleController::class, 'index']);
            Route::post('schedules',              [ScheduleController::class, 'upsert']);
            Route::delete('schedules/{schedule}', [ScheduleController::class, 'destroy']);
            Route::post('schedules/block',        [ScheduleController::class, 'block']);

            Route::get('appointments/stats',              [DoctorAppointmentController::class, 'stats']);
            Route::get('appointments',                    [DoctorAppointmentController::class, 'index']);
            Route::get('appointments/{appointment}',      [DoctorAppointmentController::class, 'show']);
            Route::post('appointments/{appointment}/confirm',  [DoctorAppointmentController::class, 'confirm']);
            Route::post('appointments/{appointment}/cancel',   [DoctorAppointmentController::class, 'cancel']);
            Route::post('appointments/{appointment}/complete', [DoctorAppointmentController::class, 'complete']);
        });

        // Rutas de paciente
        Route::middleware('role:patient')->prefix('patient')->group(function () {
            Route::get('appointments',                     [PatientAppointmentController::class, 'index']);
            Route::post('appointments',                    [PatientAppointmentController::class, 'store']);
            Route::get('appointments/{appointment}',       [PatientAppointmentController::class, 'show']);
            Route::post('appointments/{appointment}/cancel',[PatientAppointmentController::class, 'cancel']);
        });
    });
});
