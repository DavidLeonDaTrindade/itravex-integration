<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AcceptDbConnParam
{
    /** @var string[] */
    private array $allowed = ['mysql', 'mysql_cli2'];

    public function handle(Request $request, Closure $next)
    {
        $incoming = $request->input('db_connection', $request->query('db_connection'));

        if (is_string($incoming) && in_array($incoming, $this->allowed, true)) {
            session(['db_connection' => $incoming]);
        }

        if (!in_array(session('db_connection'), $this->allowed, true)) {
            session(['db_connection' => 'mysql']);
        }

        return $next($request);
    }
}
