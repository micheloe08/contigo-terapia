<?php

use App\Http\Controllers\Public\CertificateVerifyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Verificación pública de certificados (sin autenticación)
Route::get('/verify/{certificateNumber}', [CertificateVerifyController::class, 'show']);
