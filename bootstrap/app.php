<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\EnsureClientConnection;
use App\Http\Middleware\AcceptDbConnParam;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        // api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Aliases para usarlos por nombre en rutas/grupos
        $middleware->alias([
            'client.db'      => EnsureClientConnection::class,
            'accept-db-conn' => AcceptDbConnParam::class,
        ]);

        // (Opcional) Para que apliquen a TODAS las peticiones web:
        // $middleware->web([
        //     AcceptDbConnParam::class,
        //     EnsureClientConnection::class,
        // ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
