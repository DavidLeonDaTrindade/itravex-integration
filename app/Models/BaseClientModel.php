<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

abstract class BaseClientModel extends Model
{
    public function getConnectionName()
    {
        // 1) Prioriza lo que pusimos en sesión al hacer login
        if (session()->has('db_conn')) {
            return session('db_conn');
        }

        // 2) Fallback si hay usuario autenticado (por si se pierde la sesión)
        if (Auth::check() && !empty(Auth::user()->db_connection)) {
            return Auth::user()->db_connection;
        }

        // 3) Default del proyecto
        return config('database.default');
    }
}
