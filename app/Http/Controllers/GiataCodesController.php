<?php

namespace App\Http\Controllers;

use App\Models\GiataProperty;
use App\Models\GiataProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GiataCodesController extends Controller
{
    public function index(Request $req)
    {
        // Si el frontend manda Accept: application/json, esto será true
        $wantsJson = $req->expectsJson() || $req->ajax() || $req->header('Accept') === 'application/json';

        try {
            $search  = trim((string) $req->input('q', ''));
            $perPage = max(5, min((int) $req->input('per_page', 25), 200));
            $page    = max(1, (int) $req->input('page', 1));

            // -----------------------------
            // 1) Proveedores seleccionados
            // -----------------------------
            $providerCodesFilter = (array) $req->input('providers', []);
            if (empty($providerCodesFilter)) {
                $providerCodesFilter = (array) $req->query('providers', []);
            }
            $providerCodesFilter = array_values(array_filter($providerCodesFilter, fn($v) => trim((string)$v) !== ''));

            $providersQuery = GiataProvider::query()->orderBy('provider_code');

            if (!empty($providerCodesFilter)) {
                $providersQuery->whereIn('provider_code', $providerCodesFilter);
            }

            // incluye 'name' si existe en tu tabla (tu frontend lo intenta usar)
            $providers = $providersQuery->get(['id', 'provider_code', 'provider_type',]);

            $providerFilterIds = !empty($providerCodesFilter)
                ? $providers->pluck('id')
                : collect();

            // -----------------------------
            // 2) Filtro GIATA IDs (MUY IMPORTANTE)
            // -----------------------------
            $giataIds = (array) $req->input('giata_ids', []);
            if (empty($giataIds)) {
                $giataIds = (array) $req->query('giata_ids', []);
            }

            // limpiar: solo números
            $giataIds = array_values(array_unique(array_filter($giataIds, function ($v) {
                $v = trim((string)$v);
                return $v !== '' && preg_match('/^\d+$/', $v);
            })));

            // -----------------------------
            // 3) Query base hoteles + codes activos
            // -----------------------------
            $query = GiataProperty::query()
                ->with(['codes' => function ($q) use ($providerFilterIds) {
                    $q->active()
                        ->with('provider:id,provider_code,provider_type');

                    if ($providerFilterIds->isNotEmpty()) {
                        $q->whereIn('provider_id', $providerFilterIds);
                    }
                }]);

            // aplicar filtro GIATA ids si hay
            if (!empty($giataIds)) {
                $query->whereIn('giata_id', $giataIds);
            }

            // -----------------------------
            // 4) Búsqueda libre
            // -----------------------------
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    if (ctype_digit($search)) {
                        $q->orWhere('giata_id', (int) $search);
                    }

                    $q->orWhere('name', 'like', "%{$search}%");

                    $q->orWhereHas('codes', function ($qc) use ($search) {
                        $qc->where('code_value', 'like', "%{$search}%");
                    });

                    $q->orWhereHas('codes.provider', function ($qp) use ($search) {
                        $qp->where('provider_code', 'like', "%{$search}%");
                    });
                });
            }

            // -----------------------------
            // 5) Si NO quiere JSON, devolvemos vista
            // -----------------------------
            if (!$wantsJson) {
                // tu vista: asegúrate de pasar $giataIdsString si lo necesitas
                $giataIdsString = implode(', ', $giataIds);

                return view('giata.codes', [
                    'giataIdsString' => $giataIdsString,
                ]);
            }

            // -----------------------------
            // 6) JSON: paginar + mapear
            // -----------------------------
            $pageObj = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

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
                    'codes'      => $map,
                ];
            })->values();

            return response()->json([
                'providers' => $providers,
                'data'      => $rows,
                'meta'      => [
                    'current_page' => $pageObj->currentPage(),
                    'per_page'     => $pageObj->perPage(),
                    'total'        => $pageObj->total(),
                    'last_page'    => $pageObj->lastPage(),
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('GIATA codes index error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            // si el frontend esperaba JSON, devolvemos JSON (no HTML)
            if ($wantsJson) {
                return response()->json([
                    'message' => 'Error interno al cargar GIATA codes.',
                    'error'   => $e->getMessage(),
                ], 500);
            }

            throw $e;
        }
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
            return response()->json([]);
        }

        // 1) Buscar hoteles locales
        $rows = \DB::table('hotels')
            ->select('name', 'codser')
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(25)
            ->get();

        // 2) Agregar GIATA a cada hotel usando giata_properties
        $mapped = $rows->map(function ($h) {
            $giata = \DB::table('giata_properties')
                ->where('name', $h->name)       // EXACT MATCH (puede mejorarse si quieres)
                ->value('giata_id');

            return [
                'name'     => $h->name,
                'codser'   => $h->codser,
                'giata_id' => $giata ?? null,   // Si no lo encuentra, null
            ];
        });

        return response()->json($mapped);
    }
}
