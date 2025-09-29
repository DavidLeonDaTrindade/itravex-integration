<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureClientConnection;
use App\Http\Middleware\AcceptDbConnParam;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        // api: __DIR__ . '/../routes/api.php',
        // health: '/up', // opcional en Laravel 11
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Aliases opcionales por si quieres referenciarlos por nombre
        $middleware->alias([
            'client.db'      => EnsureClientConnection::class,
            'accept-db-conn' => AcceptDbConnParam::class,
        ]);

        // Que se ejecuten SIEMPRE en el grupo 'web' (orden importa):
        // 1) aceptar y guardar la conexión elegida (sesión)
        // 2) fijar la conexión por defecto
        $middleware->appendToGroup('web', [
            AcceptDbConnParam::class,
            EnsureClientConnection::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })
    ->create();
