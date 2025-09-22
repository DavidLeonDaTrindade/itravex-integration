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
}
