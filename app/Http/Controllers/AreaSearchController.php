<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AreaSearchController extends Controller
{
    public function search(Request $request)
    {
        $request->validate([
            'q'     => 'required|string|min:2|max:50',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        $q     = trim($request->input('q'));
        $limit = (int)($request->input('limit', 10));

        // ✅ Usar la conexión por defecto ya fijada por el middleware
        $rows = DB::table('zones')
            ->select(['id', 'code', 'name'])
            ->where('code', 'LIKE', 'A-%') // solo Áreas
            ->where(function ($qq) use ($q) {
                // Con utf8mb4_unicode_ci ya es case-insensitive; mantenemos UPPER por seguridad/compatibilidad
                $qq->whereRaw('UPPER(name) LIKE UPPER(?)', [$q . '%'])
                   ->orWhereRaw('UPPER(name) LIKE UPPER(?)', ['% ' . $q . '%'])
                   ->orWhereRaw('UPPER(name) LIKE UPPER(?)', ['%' . $q . '%']);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json(['items' => $rows]);
    }

    // Hoteles por zona
    public function hotels(Request $request, string $code)
    {
        $request->merge(['code' => $code]);
        $request->validate([
            'code' => ['required', 'string', 'regex:/^A-\d+$/'],
        ]);

        // ✅ Usar la conexión por defecto ya fijada por el middleware
        $rows = DB::table('hotels')
            ->select(['codser', 'name'])
            ->where('zone_code', $code)
            ->orderBy('name')
            ->get();

        return response()->json([
            'count' => $rows->count(),
            'items' => $rows,
        ]);
    }
}
