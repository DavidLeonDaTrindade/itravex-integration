<?php

// app/Http/Controllers/GiataProviderController.php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\GiataProvider;
use Illuminate\Support\Str;
use App\Jobs\SyncGiataProvidersJob;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;



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

        if (in_array($type, ['gds', 'tourOperator'], true)) {
            $query->where('provider_type', $type);
        }

        // Ordenamos por el “identificador” visible
        $query->orderBy('provider_code');

        $providers = $query->paginate($perPage)->appends(compact('q', 'type', 'perPage'));

        $totals = [
            'all'          => GiataProvider::count(),
            'gds'          => GiataProvider::where('provider_type', 'gds')->count(),
            'tourOperator' => GiataProvider::where('provider_type', 'tourOperator')->count(),
        ];

        return view('giata.providers.index', compact('providers', 'q', 'type', 'perPage', 'totals'));
    }

    public function search(Request $request)
    {
        $q     = trim((string) $request->query('q', ''));
        $type  = $request->query('type'); // 'gds' | 'tourOperator' | null
        $limit = (int) $request->query('limit', 20);

        $query = GiataProvider::query()
            ->select(['id', 'provider_code', 'provider_type', 'updated_at'])
            // ✅ fecha REAL: última actualización en giata_property_codes para ese provider
            ->selectSub(function ($q) {
                $q->from('giata_property_codes as gpc')
                    ->selectRaw('MAX(gpc.updated_at)')
                    ->whereColumn('gpc.provider_id', 'giata_providers.id');
            }, 'last_codes_updated_at');

        if ($q !== '') {
            $query->where('provider_code', 'like', "%{$q}%");
        }

        if (in_array($type, ['gds', 'tourOperator'], true)) {
            $query->where('provider_type', $type);
        }

        $query->orderBy('provider_code');

        $items = $query->limit($limit)->get();

        $totals = [
            'all'          => GiataProvider::count(),
            'gds'          => GiataProvider::where('provider_type', 'gds')->count(),
            'tourOperator' => GiataProvider::where('provider_type', 'tourOperator')->count(),
        ];

        return response()->json([
            'data'   => $items,
            'count'  => $items->count(),
            'totals' => $totals,
        ]);
    }
    public function sync(Request $request)
    {
        Log::info('[GIATA SYNC] request received', [
            'provider_code_raw' => $request->input('provider_code'),
            'user_id' => optional($request->user())->id,
        ]);
        $request->validate([
            'provider_code' => ['required', 'string', 'max:100'],
        ]);

        // Normaliza igual que ya haces
        $code = Str::lower(trim($request->input('provider_code')));
        $code = preg_replace('/\s+/', '_', $code);
        $code = preg_replace('/_+/', '_', $code);

        // 1) Si no existe, intentar CREARLO usando el comando giata:sync-providers --provider=
        $provider = GiataProvider::whereRaw('LOWER(provider_code)=?', [$code])->first();

        if (!$provider) {
            // Esto rellena giata_providers si el provider existe en GIATA (gds o tourOperator)
            $exit = Artisan::call('giata:sync-providers', [
                '--provider' => $code,
                // no pasamos --type para que el comando pruebe ambos (según lo dejaste)
            ]);

            // refrescar desde BD
            $provider = GiataProvider::whereRaw('LOWER(provider_code)=?', [$code])->first();

            if (!$provider) {
                Log::warning('[GIATA] Provider no creado por sync-providers', [
                    'provider_code' => $code,
                    'exit' => $exit,
                    'artisan_output' => Artisan::output(),
                ]);

                return response()->json([
                    'ok' => false,
                    'message' => "No se pudo crear '{$code}'. Revisa que exista en GIATA (gds/tourOperator).",
                    'details' => trim(Artisan::output()),
                ], 422);
            }
        }

        // 2) Encolar sync-properties (esto ya lo tienes ok)
        dispatch(new SyncGiataProvidersJob(
            providers: [$code],
            providersWanted: [$code],
            saveCodes: true,
            onlyActive: true,
            sleepMs: 100
        ));

        return response()->json([
            'ok' => true,
            'message' => "Job encolado para '{$code}'.",
            'provider_code' => $code,
            'created' => true,
            'provider_type' => $provider->provider_type,
        ]);
    }
}
