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

        $q = trim($request->input('q'));
        $limit = (int)($request->input('limit', 10));

        $rows = DB::table('zones')
            ->select(['id', 'code', 'name'])
            ->where('code', 'LIKE', 'A-%') // solo Áreas
            ->where(function ($qq) use ($q) {
                $qq->whereRaw('UPPER(name) LIKE UPPER(?)', [$q . '%'])
                    ->orWhereRaw('UPPER(name) LIKE UPPER(?)', ['% ' . $q . '%'])
                    ->orWhereRaw('UPPER(name) LIKE UPPER(?)', ['%' . $q . '%']);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return response()->json(['items' => $rows]);
    }
    // NUEVO: hoteles por zona
    public function hotels(Request $request, string $code)
    {
        // Valida que sea A-xxxx
        $request->merge(['code' => $code]);
        $request->validate([
            'code' => ['required', 'string', 'regex:/^A-\d+$/'],
        ]);

        // Trae los hoteles cuya zone_code == A-xxxx
        $rows = DB::table('hotels') // <- si tu tabla tiene otro nombre, cámbialo aquí
            ->select(['codser', 'name'])
            ->where('zone_code', $code)
            ->orderBy('name')        // o por codser si prefieres
            ->get();

        return response()->json([
            'count' => $rows->count(),
            'items' => $rows,
        ]);
    }
}
