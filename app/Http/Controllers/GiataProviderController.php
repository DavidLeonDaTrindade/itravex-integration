<?php

// app/Http/Controllers/GiataProviderController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GiataProvider;

class GiataProviderController extends Controller
{
    public function index(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $type    = $request->query('type');   // 'gds' | 'tourOperator' | null
        $perPage = (int) $request->query('per_page', 20);

        $query = GiataProvider::query();

        if ($q !== '') {
            // Buscamos por provider_code (no tenemos nombre real)
            $query->where('provider_code', 'like', "%{$q}%");
        }

        if (in_array($type, ['gds','tourOperator'], true)) {
            $query->where('provider_type', $type);
        }

        // Ordenamos por el “identificador” visible
        $query->orderBy('provider_code');

        $providers = $query->paginate($perPage)->appends(compact('q','type','perPage'));

        $totals = [
            'all'          => GiataProvider::count(),
            'gds'          => GiataProvider::where('provider_type','gds')->count(),
            'tourOperator' => GiataProvider::where('provider_type','tourOperator')->count(),
        ];

        return view('giata.providers.index', compact('providers','q','type','perPage','totals'));
    }

    public function search(Request $request)
    {
        $q       = trim((string) $request->query('q', ''));
        $type    = $request->query('type'); // 'gds' | 'tourOperator' | null
        $limit   = (int) $request->query('limit', 20);

        $query = GiataProvider::query();

        if ($q !== '') {
            // No tenemos “name”, así que buscamos por provider_code
            $query->where('provider_code', 'like', "%{$q}%");
        }

        if (in_array($type, ['gds','tourOperator'], true)) {
            $query->where('provider_type', $type);
        }

        $query->orderBy('provider_code');

        $items = $query->limit($limit)->get(['id','provider_code','provider_type','updated_at']);

        // (Opcional) totales para los chips; barato si tu tabla es pequeña, si no, puedes quitarlos
        $totals = [
            'all'          => GiataProvider::count(),
            'gds'          => GiataProvider::where('provider_type','gds')->count(),
            'tourOperator' => GiataProvider::where('provider_type','tourOperator')->count(),
        ];

        return response()->json([
            'data'   => $items,
            'count'  => $items->count(),
            'totals' => $totals,
        ]);
    }
}

