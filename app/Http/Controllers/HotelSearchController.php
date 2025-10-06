<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HotelSearchController extends Controller
{
    /**
     * GET /search/hotels
     * Autocompleta hoteles por nombre. Opcional: filtrar por zona con ?zoneCode=A-123
     *
     * Query params:
     *  - q        (string, required, 2–80)
     *  - limit    (int, optional, 1–20; default 10)
     *  - zoneCode (A-####, optional)
     */
    public function search(Request $request)
    {
        $request->validate([
            'q'        => 'required|string|min:2|max:80',
            'limit'    => 'nullable|integer|min:1|max:20',
            'zoneCode' => ['nullable', 'string', 'regex:/^A-\d+$/'],
        ]);

        $q        = trim($request->input('q'));
        $limit    = (int) ($request->input('limit', 10));
        $zoneCode = $request->input('zoneCode');

        $rows = DB::table('hotels')
            ->leftJoin('zones', 'zones.code', '=', 'hotels.zone_code') // 👈
            ->select([
                'hotels.codser',
                'hotels.name',
                'hotels.zone_code',
                DB::raw('COALESCE(zones.name, "") as zone_name'),       // 👈 nombre de la zona
            ])
            ->when($zoneCode, fn($qq) => $qq->where('hotels.zone_code', $zoneCode))
            ->where(function ($qq) use ($q) {
                $qq->whereRaw('UPPER(hotels.name) LIKE UPPER(?)', [$q . '%'])
                    ->orWhereRaw('UPPER(hotels.name) LIKE UPPER(?)', ['% ' . $q . '%'])
                    ->orWhereRaw('UPPER(hotels.name) LIKE UPPER(?)', ['%' . $q . '%']);
            })
            ->orderBy('hotels.name')
            ->limit($limit)
            ->get();


        return response()->json([
            'count' => $rows->count(),
            'items' => $rows,
        ]);
    }
}
