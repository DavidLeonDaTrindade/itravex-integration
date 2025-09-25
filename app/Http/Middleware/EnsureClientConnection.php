// app/Http/Middleware/EnsureClientConnection.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureClientConnection
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('db_conn') && Auth::check()) {
            session(['db_conn' => Auth::user()->db_connection ?: config('database.default')]);
        }
        return $next($request);
    }
}
