<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes & Scheduled Tasks
|--------------------------------------------------------------------------
|
| Aquí puedes registrar comandos de consola personalizados o tareas
| programadas para el scheduler de Laravel. Estas tareas se ejecutarán
| cuando el cron del servidor ejecute `php artisan schedule:run`.
|
*/

// Ejemplo opcional (puedes dejarlo si quieres)
Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// 🕒 Sincronización mensual de proveedores GIATA
Schedule::command('giata:sync-providers')
    ->monthlyOn(1, '03:30')
    ->withoutOverlapping()
    ->sendOutputTo(storage_path('logs/giata_sync.log'));


