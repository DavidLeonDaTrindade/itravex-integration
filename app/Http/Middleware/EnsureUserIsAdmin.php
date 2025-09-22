<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user(); // viene del middleware 'auth'

        if (!$user || !$user->is_admin) {
            abort(403);
        }

        return $next($request);
    }
}
