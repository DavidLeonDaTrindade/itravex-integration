<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItravexReservationController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\AreaSearchController;



Route::get('/', fn() => view('welcome'))->name('home');

// Rutas protegidas: solo usuarios logueados (sin verificación de email)
Route::middleware(['auth'])->group(function () {

    // ---- Perfil (Breeze) ----
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ---- Dashboard ----
    Route::get('/dashboard', fn() => view('dashboard'))->name('dashboard');

    // ---- Availability / Itravex ----
    Route::view('/availability/form', 'availability.form')->name('availability.form');

    Route::get('/availability/select-zones', [AvailabilityController::class, 'showZoneForm'])
        ->name('availability.selectZones');

    // Buscar disponibilidad (GET y POST para que la paginación funcione)
    Route::match(['GET', 'POST'], '/availability/search', [AvailabilityController::class, 'checkAvailability'])
        ->name('availability.search');

    // Lock (mostrar/confirmar y enviar)
    Route::get('/availability/lock', [AvailabilityController::class, 'showLockForm'])
        ->name('availability.lock.form');
    Route::post('/availability/lock', [AvailabilityController::class, 'submitLock'])
        ->name('availability.lock.submit');

    // Cerrar reserva
    Route::post('/availability/close', [AvailabilityController::class, 'closeReservation'])
        ->name('availability.close');

    // Cancelación
    Route::get('/availability/cancel', [AvailabilityController::class, 'showCancelForm'])
        ->name('availability.cancel.form');
    Route::post('/availability/cancel', [AvailabilityController::class, 'cancelReservation'])
        ->name('availability.cancel');

    // ---- Status Itravex (usa ItravexReservationController) ----
    Route::get('/itravex/status', [ItravexReservationController::class, 'index'])
        ->name('itravex.status');

    // Eliminar una reserva guardada en BD (opcional: restringe a admin dentro del controller)
    Route::delete('/itravex/{id}', [ItravexReservationController::class, 'destroy'])
        ->name('itravex.destroy');

    // ---- Logs (si tienes una vista simple) ----
    Route::get('/logs/itravex', [LogViewerController::class, 'itravey'])
        ->name('logs.itravex');

    Route::get('/logs/itravex/download', [LogViewerController::class, 'download'])
        ->name('logs.itravex.download');

    Route::middleware(['auth', 'throttle:6,1'])->post('email/verification-notification', function () {
        return back()->with('status', 'Email verification is disabled.');
    })->name('verification.send');
    Route::middleware('auth')->group(function () {
        // Cambiar contraseña autenticado
        Route::put('password', [PasswordController::class, 'update'])
            ->name('password.update');
    });

    Route::get('/areas', [AreaSearchController::class, 'search'])
    ->middleware('throttle:30,1');
});

require __DIR__ . '/auth.php';
