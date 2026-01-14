<?php

namespace App\Http\Controllers;

use App\Models\GiataProperty;
use App\Models\GiataProvider;
use App\Models\GiataPropertyCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Str;

class GiataCodesController extends Controller
{
    public function index(Request $req)
    {
        $wantsJson = $req->expectsJson() || $req->ajax() || $req->header('Accept') === 'application/json';

        try {
            $search  = trim((string) $req->input('q', ''));
            $perPage = max(5, min((int) $req->input('per_page', 25), 200));
            $page    = max(1, (int) $req->input('page', 1));

            // 1) Proveedores seleccionados
            $providerCodesFilter = (array) $req->input('providers', []);
            if (empty($providerCodesFilter)) {
                $providerCodesFilter = (array) $req->query('providers', []);
            }
            $providerCodesFilter = array_values(array_filter($providerCodesFilter, fn($v) => trim((string)$v) !== ''));

            $providersQuery = GiataProvider::query()->orderBy('provider_code');

            if (!empty($providerCodesFilter)) {
                $providersQuery->whereIn('provider_code', $providerCodesFilter);
            }

            $providers = $providersQuery->get(['id', 'provider_code', 'provider_type']);

            $providerFilterIds = !empty($providerCodesFilter)
                ? $providers->pluck('id')
                : collect();

            // 2) Filtro GIATA IDs
            $giataIds = (array) $req->input('giata_ids', []);
            if (empty($giataIds)) {
                $giataIds = (array) $req->query('giata_ids', []);
            }

            $giataIds = array_values(array_unique(array_filter($giataIds, function ($v) {
                $v = trim((string)$v);
                return $v !== '' && preg_match('/^\d+$/', $v);
            })));

            // 3) Query base + codes activos
            $query = GiataProperty::query()
                ->with(['codes' => function ($q) use ($providerFilterIds) {
                    $q->active()
                        ->with('provider:id,provider_code,provider_type');

                    if ($providerFilterIds->isNotEmpty()) {
                        $q->whereIn('provider_id', $providerFilterIds);
                    }
                }]);

            if (!empty($giataIds)) {
                $query->whereIn('giata_id', $giataIds);
            }

            // 3.5) Reglas de coincidencia:
            //  - 1 provider => 1 match
            //  - 2+ providers => mínimo 2 match
            if ($providerFilterIds->isNotEmpty()) {
                $minMatches = ($providerFilterIds->count() >= 2) ? 2 : 1;

                $ids = GiataPropertyCode::query()
                    ->active()
                    ->whereIn('provider_id', $providerFilterIds)
                    ->select('giata_property_id')
                    ->groupBy('giata_property_id')
                    ->havingRaw('COUNT(DISTINCT provider_id) >= ?', [$minMatches]);

                // ojo: giata_property_id apunta a giata_properties.id (PK)
                $query->whereIn('id', $ids);
            }

            // 4) búsqueda libre
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

            // 5) vista
            if (!$wantsJson) {
                $giataIdsString = session()->pull('giata_ids_string', '');

                return response()
                    ->view('giata.codes', [
                        'giataIdsString' => $giataIdsString,
                    ])
                    ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }

            // 6) JSON
            $pageObj = $query
                ->orderByRaw("(name IS NULL OR name = '') ASC")
                ->orderBy('name')
                ->paginate($perPage, ['*'], 'page', $page);

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

        $exportAll = (bool) $req->input('export_all', false);

        $perPage = max(5, min((int) $req->input('per_page', 25), 200));
        $page    = max(1, (int) $req->input('page', 1));

        $providerCodesFilter = (array) $req->input('providers', []);
        $providerCodesFilter = array_values(array_filter($providerCodesFilter, fn($v) => trim((string)$v) !== ''));

        $giataIds = (array) $req->input('giata_ids', []);
        $giataIds = array_values(array_unique(array_filter($giataIds, function ($v) {
            $v = trim((string)$v);
            return $v !== '' && preg_match('/^\d+$/', $v);
        })));

        $providersQuery = GiataProvider::query()->orderBy('provider_code');
        if (!empty($providerCodesFilter)) {
            $providersQuery->whereIn('provider_code', $providerCodesFilter);
        }
        $providers = $providersQuery->get(['id', 'provider_code', 'provider_type']);

        $providerFilterIds = !empty($providerCodesFilter) ? $providers->pluck('id') : collect();

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

        if ($providerFilterIds->isNotEmpty()) {
            $minMatches = ($providerFilterIds->count() >= 2) ? 2 : 1;

            $ids = GiataPropertyCode::query()
                ->active()
                ->whereIn('provider_id', $providerFilterIds)
                ->select('giata_property_id')
                ->groupBy('giata_property_id')
                ->havingRaw('COUNT(DISTINCT provider_id) >= ?', [$minMatches]);

            $query->whereIn('id', $ids);
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
            fwrite($out, "\xEF\xBB\xBF");

            $header = array_merge(['Hotel', 'GIATA ID'], $providers->pluck('provider_code')->all());
            fputcsv($out, $header, ';');

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
                $query
                    ->orderByRaw("(name IS NULL OR name = '') ASC")
                    ->orderBy('name')
                    ->chunk(300, function ($chunk) use ($writeProp, $out) {
                        foreach ($chunk as $prop) $writeProp($prop);
                        fflush($out);
                    });
            } else {
                $pageObj = $query
                    ->orderByRaw("(name IS NULL OR name = '') ASC")
                    ->orderBy('name')
                    ->paginate($perPage, ['*'], 'page', $page);

                foreach ($pageObj->items() as $prop) $writeProp($prop);
            }

            fclose($out);
        }, $filename, [
            'Content-Type'  => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'no-store, no-cache',
        ]);
    }

    public function browser()
    {
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
