<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    // Mostrar formulario en "/"
    Route::get('/', [AuthenticatedSessionController::class, 'create'])->name('login');

    // Aceptar el submit del formulario también en "/"
    Route::post('/', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');

    // (Opcional) Mantén también /login por compatibilidad si quieres
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:login');
});

Route::middleware('auth')->group(function () {
    // Logout
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
