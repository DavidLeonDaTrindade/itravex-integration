<?php

namespace App\Http\Controllers;

use App\Models\GiataProperty;
use App\Models\GiataProvider;
use Illuminate\Http\Request;

class GiataCodesController extends Controller
{
    public function index(Request $req)
    {
        $search  = trim((string)$req->query('q', ''));
        $perPage = max(5, min((int)$req->query('per_page', 25), 200));
        $page    = (int)$req->query('page', 1);

        // --- Filtro por proveedores seleccionados (providers[] con provider_code) ---
        $providerCodesFilter = array_filter((array) $req->input('providers', []));

        if (!empty($providerCodesFilter)) {
            // Sólo los proveedores que ha elegido el usuario
            $providers = GiataProvider::query()
                ->whereIn('provider_code', $providerCodesFilter)
                ->orderBy('provider_code')
                ->get(['id', 'provider_code', 'provider_type']);
        } else {
            // Todos los proveedores (como antes)
            $providers = GiataProvider::query()
                ->orderBy('provider_code')
                ->get(['id', 'provider_code', 'provider_type']);
        }

        // IDs de proveedor a aplicar al JOIN de códigos (si hay filtro)
        $providerFilterIds = !empty($providerCodesFilter)
            ? $providers->pluck('id')
            : collect();

        // --- Query de hoteles + códigos activos ---
        $query = GiataProperty::query()
            ->with(['codes' => function ($q) use ($providerFilterIds) {
                $q->active()
                  ->with('provider:id,provider_code,provider_type');

                // Si el usuario ha filtrado proveedores, sólo esos
                if ($providerFilterIds->isNotEmpty()) {
                    $q->whereIn('provider_id', $providerFilterIds);
                }
            }]);

        // --- Filtro GIATA IDs (lista del Excel / textarea) ---
        $giataIds = array_filter((array) $req->input('giata_ids', []));
        if (!empty($giataIds)) {
            $query->whereIn('giata_id', $giataIds);
        }

        // --- Filtro de búsqueda libre (nombre, GIATA, código, provider_code) ---
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('giata_id', (int)$search);
                }
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhereHas('codes', fn($qc) =>
                    $qc->where('code_value', 'like', "%{$search}%")
                );
                $q->orWhereHas('codes.provider', fn($qp) =>
                    $qp->where('provider_code', 'like', "%{$search}%")
                );
            });
        }

        // --- Paginación ---
        $pageObj = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        // --- Mapeo GIATA -> [provider_id => "code | code"] ---
        $rows = $pageObj->getCollection()->map(function ($prop) {
            $map = [];
            foreach ($prop->codes as $c) {
                $map[$c->provider_id] = isset($map[$c->provider_id])
                    ? ($map[$c->provider_id] . ' | ' . $c->code_value)
                    : $c->code_value;
            }
            return [
                'giata_id'   => (int) $prop->giata_id,
                'hotel_name' => $prop->name,
                'codes'      => $map, // provider_id => "code | code"
            ];
        })->values();

        return response()->json([
            // Proveedores que usará el frontend para columnas (ya filtrados por el usuario si toca)
            'providers' => $providers,  // [{id, provider_code, provider_type, ...}]
            'data'      => $rows,
            'meta'      => [
                'current_page' => $pageObj->currentPage(),
                'per_page'     => $pageObj->perPage(),
                'total'        => $pageObj->total(),
                'last_page'    => $pageObj->lastPage(),
            ],
        ]);
    }

    public function browser()
    {
        // Vista que consume el JSON de /giata/codes + lista de GIATA cargada desde Excel
        $giataIdsString = session('giata_ids_string', '');

        return view('giata.codes', [
            'giataIdsString' => $giataIdsString,
        ]);
    }

    public function hotelSuggest(Request $request)
    {
        $term = trim((string)$request->query('q', ''));

        if (strlen($term) < 2) {
            return response()->json([]); // para evitar cargas innecesarias
        }

        $rows = \DB::table('hotels')
            ->select('codser', 'name')
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(25)
            ->get();

        return response()->json($rows);
    }
}
