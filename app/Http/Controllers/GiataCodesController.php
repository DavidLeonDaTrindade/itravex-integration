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

        // Columnas dinámicas: todos los proveedores (con tus accessors)
        $providers = GiataProvider::query()
            ->orderBy('provider_code')
            ->get(['id', 'provider_code', 'provider_type']);

        // Hoteles + códigos activos
        $query = GiataProperty::query()
            ->with(['codes' => function ($q) {
                $q->active()->with('provider:id,provider_code,provider_type');
            }]);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                if (ctype_digit($search)) {
                    $q->orWhere('giata_id', (int)$search);
                }
                $q->orWhere('name', 'like', "%{$search}%");
                $q->orWhereHas('codes', fn($qc) => $qc->where('code_value', 'like', "%{$search}%"));
                $q->orWhereHas('codes.provider', fn($qp) => $qp->where('provider_code', 'like', "%{$search}%"));
            });
        }

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
                'codes'      => $map, // provider_id => "code | code"
            ];
        })->values();

        return response()->json([
            'providers' => $providers,  // [{id, provider_code, provider_type, name, type, code}]
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
        // Vista que consume el JSON de /giata/codes
        return view('giata.codes');
    }
}
