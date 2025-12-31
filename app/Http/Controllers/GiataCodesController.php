<?php

namespace App\Http\Controllers;

use App\Models\GiataProperty;
use App\Models\GiataProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

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
                $giataIdsString = session()->pull('giata_ids_string', ''); // lee y BORRA

                return response()
                    ->view('giata.codes', [
                        'giataIdsString' => $giataIdsString,
                    ])
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
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

            return response()
                ->json([
                    'providers' => $providers,
                    'data'      => $rows,
                    'meta'      => [
                        'current_page' => $pageObj->currentPage(),
                        'per_page'     => $pageObj->perPage(),
                        'total'        => $pageObj->total(),
                        'last_page'    => $pageObj->lastPage(),
                    ],
                ])
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
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

    public function export(Request $req): StreamedResponse
    {
        $search = trim((string) $req->input('q', ''));

        // ✅ flag para exportar TODO
        $exportAll = (bool) $req->input('export_all', false);

        // Si NO exportAll, respeta paginación
        $perPage = max(5, min((int) $req->input('per_page', 25), 200));
        $page    = max(1, (int) $req->input('page', 1));

        // providers[] (provider_code)
        $providerCodesFilter = (array) $req->input('providers', []);
        $providerCodesFilter = array_values(array_filter($providerCodesFilter, fn($v) => trim((string)$v) !== ''));

        // giata_ids[]
        $giataIds = (array) $req->input('giata_ids', []);
        $giataIds = array_values(array_unique(array_filter($giataIds, function ($v) {
            $v = trim((string)$v);
            return $v !== '' && preg_match('/^\d+$/', $v);
        })));

        // Providers (columnas)
        $providersQuery = GiataProvider::query()->orderBy('provider_code');
        if (!empty($providerCodesFilter)) {
            $providersQuery->whereIn('provider_code', $providerCodesFilter);
        }
        $providers = $providersQuery->get(['id', 'provider_code', 'provider_type']);

        $providerFilterIds = !empty($providerCodesFilter) ? $providers->pluck('id') : collect();

        // Query base igual que index()
        $query = GiataProperty::query()
            ->with(['codes' => function ($q) use ($providerFilterIds) {
                $q->active()->with('provider:id,provider_code,provider_type');
                if ($providerFilterIds->isNotEmpty()) {
                    $q->whereIn('provider_id', $providerFilterIds);
                }
            }]);

        if (!empty($giataIds)) {
            $query->whereIn('giata_id', $giataIds);
        }

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

        $safeQ = $search !== '' ? Str::slug(Str::limit($search, 40, ''), '_') : 'all';
        $filename = $exportAll
            ? "giata_codes_{$safeQ}_ALL.csv"
            : "giata_codes_{$safeQ}_page_{$page}.csv";

        return response()->streamDownload(function () use ($providers, $query, $exportAll, $perPage, $page) {
            $out = fopen('php://output', 'w');

            // BOM UTF-8 para Excel
            fwrite($out, "\xEF\xBB\xBF");

            // header
            $header = array_merge(['Hotel', 'GIATA ID'], $providers->pluck('provider_code')->all());
            fputcsv($out, $header, ';');

            // Helper para escribir una fila
            $writeProp = function ($prop) use ($out, $providers) {
                $map = [];
                foreach ($prop->codes as $c) {
                    $map[$c->provider_id] = isset($map[$c->provider_id])
                        ? ($map[$c->provider_id] . ' | ' . $c->code_value)
                        : $c->code_value;
                }

                $line = [$prop->name ?? '', (string)$prop->giata_id];

                foreach ($providers as $p) {
                    $val = $map[$p->id] ?? '';
                    $val = preg_replace('/\s+/', ' ', (string)$val);
                    $line[] = $val;
                }

                fputcsv($out, $line, ';');
            };

            if ($exportAll) {
                // ✅ Exportar TODO (sin paginación) en chunks para no petar memoria
                $query->orderBy('name')->chunk(300, function ($chunk) use ($writeProp, $out) {
                    foreach ($chunk as $prop) {
                        $writeProp($prop);
                    }
                    fflush($out);
                });
            } else {
                // Exportar solo la página actual
                $pageObj = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);
                foreach ($pageObj->items() as $prop) {
                    $writeProp($prop);
                }
            }

            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }



    public function browser()
    {
        // Vista que consume el JSON de /giata/codes + lista de GIATA cargada desde Excel
        $giataIdsString = session('giata_ids_string', '');
        return view('giata.codes', compact('giataIdsString'));
    }

    public function hotelSuggest(Request $request)
    {
        $term = trim((string)$request->query('q', ''));
        if (mb_strlen($term) < 2) return response()->json([]);

        $rows = \DB::table('giata_properties')
            ->select('name', 'giata_id')
            ->where('name', 'like', "%{$term}%")
            ->orderBy('name')
            ->limit(25)
            ->get();

        return response()->json(
            $rows->map(fn($r) => [
                'name' => $r->name,
                'giata_id' => (int) $r->giata_id,
            ])
        );
    }
}
