<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class EnsureClientConnection
{
    /** @var string[] */
    private array $allowed = ['mysql', 'mysql_cli2'];

    public function handle(Request $request, Closure $next)
    {
        // 1) Lee la seleccion guardada en sesión (por defecto mysql)
        $selected = session('db_connection', 'mysql');

        if (!in_array($selected, $this->allowed, true)) {
            $selected = 'mysql';
            session(['db_connection' => $selected]);
        }

        // 2) Si la conexión por defecto actual no coincide, cámbiala en runtime
        if (DB::getDefaultConnection() !== $selected) {
            // Importante: actualiza el config y el default del DB manager
            Config::set('database.default', $selected);
            DB::setDefaultConnection($selected);

            // Opcional pero útil: purga y reconecta para evitar conexiones cacheadas
            foreach ($this->allowed as $name) {
                DB::purge($name);
            }
            DB::reconnect($selected);
        }

        return $next($request);
    }
}
