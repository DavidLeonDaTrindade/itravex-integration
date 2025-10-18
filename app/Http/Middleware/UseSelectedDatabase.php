<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;

class UseSelectedDatabase
{
    public function handle($request, Closure $next)
    {
        // Conexiones permitidas (los NOMBRES definidos en config/database.php)
        $allowed = ['mysql', 'mysql_cli2'];

        // Lee la conexión elegida desde la sesión; por defecto 'mysql'
        $selected = session('db_connection', 'mysql');

        if (!in_array($selected, $allowed, true)) {
            $selected = 'mysql';
            session(['db_connection' => $selected]);
        }

        // Fija la conexión por defecto en runtime
        DB::setDefaultConnection($selected);

        
        return $next($request);
    }
}
