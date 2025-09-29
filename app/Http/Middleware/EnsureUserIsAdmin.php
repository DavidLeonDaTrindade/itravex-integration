<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EnsureClientConnection
{
    /** @var string[] */
    private array $allowed = ['mysql', 'mysql_cli2'];

    public function handle(Request $request, Closure $next)
    {
        $selected = session('db_connection', 'mysql');

        if (!in_array($selected, $this->allowed, true)) {
            $selected = 'mysql';
            session(['db_connection' => $selected]);
        }

        DB::setDefaultConnection($selected);

        return $next($request);
    }
}
