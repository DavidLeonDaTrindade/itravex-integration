<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AvailabilityController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ItravexReservationController;
use App\Http\Controllers\LogViewerController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\AreaSearchController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\Hotel;
use App\Models\Zone;
use App\Http\Controllers\HotelSearchController;
use App\Http\Controllers\GiataProviderController;
use App\Http\Controllers\GiataCodesController;
use App\Http\Controllers\GiataPropertyRawController;
use App\Http\Controllers\GiataCodesBrowserController;






Route::get('/', fn() => view('welcome'))->name('home');

// Rutas protegidas: solo usuarios logueados (sin verificación de email)
Route::middleware(['auth'])->group(function () {

    Route::get('/_debug-db', function () {
        return response()->json([
            'session_db'  => session('db_connection'),
            'user_db'     => optional(Auth::user())->db_connection,
            'hotel_conn'  => (new Hotel)->getConnectionName(),
            'zone_conn'   => (new Zone)->getConnectionName(),
            'A210_hotels' => Hotel::where('zone_code', 'A-210')->count(),
        ]);
    })->name('debug.db');
    Route::get('/db-check', function () {
        return response()->json([
            'session'            => session('db_connection'),
            'db_default_manager' => DB::getDefaultConnection(),
            'db_config_default'  => config('database.default'),
            'database_name'      => DB::connection()->getConfig('database'),
        ]);
    });
    Route::get('/_build', function () {
        return response()->json([
            'base_path'   => base_path(),
            'git_head'    => trim(@shell_exec('git rev-parse --short HEAD')),
            'git_branch'  => trim(@shell_exec('git rev-parse --abbrev-ref HEAD')),
            'env'         => app()->environment(),
            'db_default'  => DB::getDefaultConnection(),
            'db_config'   => config('database.default'),
            'database'    => DB::connection()->getDatabaseName(),
        ]);
    })->name('debug.build');



    // ---- Ruta para cambiar de base de datos ----
    Route::post('/switch-db', function (Request $request) {
        $request->validate([
            'db_connection' => 'required|in:mysql,mysql_cli2',
        ]);

        session(['db_connection' => $request->string('db_connection')->toString()]);

        return back()->with('status', 'Base de datos cambiada correctamente.');
    })->name('db.switch');

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

    Route::get('/areas/{code}/hotels', [AreaSearchController::class, 'hotels'])
        ->middleware('throttle:30,1')
        ->where('code', 'A-\d+');

    Route::get('/search/hotels', [HotelSearchController::class, 'search'])
        ->name('search.hotels');

    Route::get('/giata/providers', [GiataProviderController::class, 'index'])
        ->name('giata.providers.index');

    Route::get('/giata/providers/search', [GiataProviderController::class, 'search'])
        ->name('giata.providers.search'); // ← nueva ruta JSON

    Route::match(['GET', 'POST'], '/giata/codes', [GiataCodesController::class, 'index'])
        ->name('giata.codes.index');


    Route::get('/giata/codes/browser', [GiataCodesController::class, 'browser'])
        ->name('giata.codes.browser');

    Route::get('/giata/hotels-suggest', [GiataCodesController::class, 'hotelSuggest']);


    Route::get('/giata/properties-raw', [GiataPropertyRawController::class, 'index'])
        ->name('giata.properties.raw.index');

    Route::get('/giata/properties-raw/cities', [GiataPropertyRawController::class, 'citySuggestions'])
        ->name('giata.properties.raw.cities');

    Route::get('/giata/properties-raw/names', [GiataPropertyRawController::class, 'nameSuggestions'])
        ->name('giata.properties.raw.names');

    Route::get('/giata/properties-raw', [GiataPropertyRawController::class, 'index'])
        ->name('giata.properties.raw.index');

    Route::get('/giata/properties-raw/export', [GiataPropertyRawController::class, 'export'])
        ->name('giata.properties.raw.export');



    Route::get('/giata/codes-browser', [GiataCodesBrowserController::class, 'index'])
        ->name('giata.codes.browser');

    Route::post('/giata/codes/upload-giata', [GiataCodesBrowserController::class, 'uploadGiata'])
        ->name('giata.codes.uploadGiata');

    Route::post('/giata/codes/export', [GiataCodesController::class, 'export'])
        ->name('giata.codes.export');

    Route::post('/giata/providers', [GiataProviderController::class, 'sync'])->name('giata.providers.sync');
});

require __DIR__ . '/auth.php';
