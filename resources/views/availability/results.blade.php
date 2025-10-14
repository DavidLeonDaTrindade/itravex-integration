<x-app-layout>
    <style>
        /* Fallback si Tailwind no está cargado */
        .hidden {
            display: none !important
        }

        .btn-sel {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            padding: .375rem .75rem;
            border-radius: .5rem;
            background: #4f46e5;
            color: #fff;
            border: 1px solid rgba(0, 0, 0, .06);
            box-shadow: 0 1px 1px rgba(0, 0, 0, .05);
            font: 600 .875rem/1.25 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            cursor: pointer;
            transition: .2s ease;
            white-space: nowrap;
        }

        .btn-sel:hover {
            background: #6366f1;
            box-shadow: 0 3px 8px rgba(79, 70, 229, .25);
            transform: translateY(-1px);
        }

        .btn-sel:active {
            transform: translateY(0);
            box-shadow: 0 1px 2px rgba(0, 0, 0, .15);
        }

        .btn-sel .spinner {
            width: 14px;
            height: 14px;
            border: 2px solid #fff;
            border-right-color: transparent;
            border-radius: 50%;
            display: none;
            animation: spin .6s linear infinite;
        }

        .btn-sel.is-loading {
            opacity: .85;
            pointer-events: none;
        }

        .btn-sel.is-loading .spinner {
            display: inline-block;
        }

        .btn-sel.is-loading .label {
            display: none;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg)
            }
        }
    </style>
    <style>
        /* Layout de cada fila de habitación (funciona con o sin Tailwind) */
        .room-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
            background: #f8fafc;
        }

        .room-left {
            min-width: 0;
        }

        .room-title {
            font: 600 .9rem/1.3 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            color: #0f172a;
            word-break: break-word;
            overflow-wrap: anywhere;
        }

        .room-meta {
            font: .875rem/1.35 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            color: #334155;
        }

        .room-right {
            display: flex;
            align-items: center;
            gap: .75rem;
            white-space: nowrap;
            margin-left: auto;
        }

        .room-price {
            font-weight: 700;
            color: #065f46;
        }

        /* Responsive: en móviles alinea arriba sin “saltar” */
        @media (max-width: 640px) {
            .room-item {
                align-items: flex-start;
            }

            .room-right {
                margin-left: auto;
            }
        }

        .card {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 1px 2px rgba(0, 0, 0, .04);
        }
    </style>
    <style>
        /* Etiquetas/badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font: 700 .72rem/1 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            padding: .25rem .45rem;
            border-radius: .5rem;
            white-space: nowrap;
            border: 1px solid rgba(0, 0, 0, .06);
        }

        /* Interna = dorado corporativo */
        .badge-internal {
            background: #FFF4D6;
            color: #8A5A00;
            border-color: #FDB31B;
        }

        /* Resaltado de la fila de habitación interna */
        .room-item.is-internal {
            position: relative;
            background: #FFF9EC;
            /* fondo cálido */
            border-color: #FDB31B;
            /* borde dorado */
            box-shadow: 0 4px 14px rgba(253, 179, 27, .28);
        }

        /* Barra lateral izquierda como acento */
        .room-item.is-internal::before {
            content: "";
            position: absolute;
            left: -1px;
            top: -1px;
            bottom: -1px;
            width: 6px;
            background: linear-gradient(180deg, #FDB31B 0%, #FFC75A 100%);
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        /* Mini etiqueta “INTERNAL” arriba a la derecha */
        .room-item.is-internal .flag-internal {
            position: absolute;
            top: -10px;
            right: 12px;
            background: #004665;
            color: #fff;
            padding: .2rem .5rem;
            border-radius: .35rem;
            font: 700 .68rem/1 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
            letter-spacing: .02em;
        }
    </style>
    <style>
        .badge {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            font: 700 .72rem/1 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            padding: .25rem .45rem;
            border-radius: .5rem;
            white-space: nowrap;
            border: 1px solid rgba(0, 0, 0, .06);
        }

        .badge-internal {
            background: #FFF4D6;
            color: #8A5A00;
            border-color: #FDB31B;
        }

        .room-item.is-internal {
            position: relative;
            background: #FFF9EC;
            border-color: #FDB31B;
            box-shadow: 0 4px 14px rgba(253, 179, 27, .28);
        }

        .room-item.is-internal::before {
            content: "";
            position: absolute;
            left: -1px;
            top: -1px;
            bottom: -1px;
            width: 6px;
            background: linear-gradient(180deg, #FDB31B 0%, #FFC75A 100%);
            border-top-left-radius: 8px;
            border-bottom-left-radius: 8px;
        }

        .room-item.is-internal .flag-internal {
            position: absolute;
            top: -10px;
            right: 12px;
            background: #004665;
            color: #fff;
            padding: .2rem .5rem;
            border-radius: .35rem;
            font: 700 .68rem/1 system-ui, -apple-system, Segoe UI, Roboto, Inter, Arial, sans-serif;
            box-shadow: 0 2px 8px rgba(0, 0, 0, .15);
            letter-spacing: .02em;
        }
    </style>
    <style>
        /* === Packs de habitaciones: mejoras de legibilidad === */
        .room-pack .desc,
        .room-pack .desc * {
            white-space: normal;
            /* sin saltos raros */
            word-break: normal;
            /* NO cortar palabra a palabra */
            overflow-wrap: anywhere;
            /* permitir cortar sólo si es necesario */
        }

        .room-pack .mono {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace;
            font-size: .75rem;
            background: #f1f5f9;
            /* slate-100 */
            padding: .125rem .375rem;
            border-radius: .375rem;
            /* rounded-md */
        }

        .room-pack .rooms-grid .room-chip {
            border: 1px solid #e2e8f0;
            /* slate-200 */
            background: #f8fafc;
            /* slate-50 */
            border-radius: .5rem;
            /* rounded-lg */
            padding: .75rem;
            /* p-3 */
        }
    </style>



    @php
    /**
     * Genera inputs hidden de distri[<hab>] para el lock:
     * - distri[i][numadl]
     * - distri[i][numnin]
     * - distri[i][edanin][k]
     */
    $buildDistriInputs = function ($roomsOcc) {
        $html = '';
        if (!is_array($roomsOcc) || empty($roomsOcc)) return $html;

        // Queremos índices 1..N (el lock-form y el controlador esperan esos índices)
        $i = 1;
        foreach ($roomsOcc as $r) {
            $numadl = (int) ($r['adl'] ?? $r['numadl'] ?? $r['adults'] ?? 2);
            $numnin = (int) ($r['chd'] ?? $r['numnin'] ?? $r['children'] ?? 0);
            $ages   = (array) ($r['ages'] ?? []); // edades de niños si llegaron en la búsqueda

            $html .= '<input type="hidden" name="distri['.$i.'][numadl]" value="'.$numadl.'">';
            $html .= '<input type="hidden" name="distri['.$i.'][numnin]" value="'.$numnin.'">';

            // edanin es opcional, solo si recibiste ages[] en la búsqueda
            $k = 0;
            foreach ($ages as $age) {
                if ($age === '' || $age === null) continue;
                $html .= '<input type="hidden" name="distri['.$i.'][edanin]['.$k.']" value="'.(int)$age.'">';
                $k++;
            }

            $i++;
        }
        return $html;
    };
@endphp



    @section('content')
    {{-- Resumen de la petición enviada (usando valores efectivos) --}}
    @php
    $eff = $effective ?? [];
    $roomsEff = is_array($eff['rooms'] ?? null) ? $eff['rooms'] : (isset($rooms) && is_array($rooms) ? $rooms : null);
    $codnacEff = $eff['codnac'] ?? null; // p.ej. de config si no lo envía el usuario
    $timeoutEff = $eff['timeout_ms'] ?? null; // timeout real que se envía al proveedor
    $perPageEff = $eff['per_page'] ?? null; // tamaño de página UI
    $fromZone = $eff['from_zone'] ?? request('codzge');
    $manualCodes = $eff['manual_codes'] ?? filled(request('hotel_codes'));
    $zoneTotal = $eff['zone_total'] ?? null;

    // Inputs crudos (por si el usuario sí los mandó)
    $codzgeIn = request('codzge');
    $hotelCodesIn = trim((string) request('hotel_codes',''));
    $feciniIn = request('fecini');
    $fecfinIn = request('fecfin');
    $numadlIn = request('numadl'); // compat single-room antiguo
    $codnacIn = strtoupper((string) request('codnac',''));
    $timeoutIn = request('timeout');
    $numrstIn = request('numrst');

    // ---- OCUPACIÓN (multi/single) ----
    // Si no llegó en effective ni en $rooms, probamos con la query (casos sin PRG)
    $roomsReq = request()->has('rooms') ? (array) request('rooms') : [];
    $roomsSrc = collect($roomsEff ?? $roomsReq);

    $adlFromRooms = (int) $roomsSrc->sum(fn($r) => (int) ($r['adl'] ?? $r['numadl'] ?? $r['adults'] ?? 0));
    $chdNumRooms = (int) $roomsSrc->sum(fn($r) => (int) ($r['chd'] ?? $r['numnin'] ?? $r['numchd'] ?? $r['children'] ?? 0));
    $chdAgesRooms = (int) $roomsSrc->sum(function ($r) {
    $ages = $r['ages'] ?? $r['edades'] ?? [];
    return is_array($ages) ? count(array_filter($ages, fn($a) => $a !== '' && $a !== null)) : 0;
    });

    // Fallbacks top-level (formularios antiguos)
    $topAdl = (int) (request('numadl') ?? request('adl') ?? request('adults') ?? 0);
    $topChd = (int) (request('numnin') ?? request('numchd') ?? request('chd') ?? request('children') ?? 0);
    $topAges = request()->input('ages', request()->input('edades', []));
    $topChdFromAges = is_array($topAges) ? (int) count(array_filter($topAges, fn($a) => $a !== '' && $a !== null)) : 0;

    // Totales (evitando doble conteo)
    $adlTotal = $adlFromRooms > 0 ? $adlFromRooms : $topAdl;
    $chdTotal = max(max($chdNumRooms, $chdAgesRooms), max($topChd, $topChdFromAges));

    // Texto final
    $occupSummary = ($adlTotal || $chdTotal)
    ? trim(($adlTotal ? "Adultos: {$adlTotal}" : '') . ($chdTotal ? ($adlTotal ? ' · ' : '') . "Niños: {$chdTotal}" : ''))
    : '—';

    // Fallbacks “bonitos”
    $codnacShown = $codnacEff ?: ($codnacIn ?: '—');
    $timeoutShown = $timeoutEff ?: ($timeoutIn ?: '—');

    // Nota explicativa de cómo se obtuvieron los hoteles
    $sourceNote = $hotelCodesIn !== ''
    ? 'Códigos manuales'
    : ($fromZone ? "Por zona: {$fromZone}" : '—');

    if ($zoneTotal && $hotelCodesIn === '' && $fromZone) {
    $sourceNote .= " (hoteles en BD: {$zoneTotal})";
    }

    // Proveedores internos (mismo criterio que en el controller)
    $internalCodtous = array_map('strtoupper', config('itravex.internal_codtous', ['LIB']));
    @endphp

    @if(request()->hasAny(['codzge','hotel_codes','fecini','fecfin','numadl']) || $codnacEff || $timeoutEff)
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <h2 class="text-base font-semibold text-slate-800 mb-2">📤 Petición enviada al proveedor</h2>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-2 text-sm text-slate-700">
            <div>
                <dt class="font-medium text-slate-600">Código de zona</dt>
                <dd>{{ $codzgeIn ?: ($fromZone ?: '—') }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Códigos de hotel</dt>
                <dd>
                    @if($hotelCodesIn !== '')
                    {{ $hotelCodesIn }}
                    @else
                    — <span class="text-slate-500">( {{ $sourceNote }} )</span>
                    @endif
                </dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Fecha inicio</dt>
                <dd>{{ $feciniIn ?: '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Fecha fin</dt>
                <dd>{{ $fecfinIn ?: '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Ocupación</dt>
                <dd>{{ $occupSummary }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">País (codnac)</dt>
                <dd>{{ $codnacShown }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Timeout (ms)</dt>
                <dd>{{ $timeoutShown }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Resultados por página (numrst)</dt>
                <dd>{{ $numrstIn ?: '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Tamaño página UI</dt>
                <dd>{{ $perPageEff ?: '—' }}</dd>
            </div>
            <div>
                <dt class="font-medium text-slate-600">Modo</dt>
                <dd>{{ request('mode','fast') === 'fast' ? 'Rápido' : 'Completo' }}</dd>
            </div>
        </dl>
    </div>
    @endif




    <div id="top" class="max-w-7xl mx-auto px-6 py-10">

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-3xl font-bold text-blue-800">🛏️ Resultados de Disponibilidad</h1>

            <div class="flex items-center gap-2">
                {{-- Botón ver petición XML --}}
                @if(!empty($firstPayload))
                <button id="btn-show-xml"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 
              text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    Ver petición XML
                </button>
                @endif
                {{-- Botón exportar CSV --}}
                <a href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 
                  text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 hover:border-slate-400 transition">
                    <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v12m0 0l-4-4m4 4l4-4M4 17h16v2H4z" />
                    </svg>
                    Exportar CSV
                </a>

                <a href="{{ route('dashboard') }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow transition">
                    ⬅️ Volver al inicio
                </a>
            </div>
        </div>

        @if(request()->get('mode') === 'fast')
        <span class="text-sm text-orange-600 font-medium ml-2">⚡ Modo rápido — resultados parciales para carga ágil</span>
        @elseif(request()->get('mode') === 'full')
        <span class="text-sm text-green-600 font-medium ml-2">📋 Modo completo — resultados completos (puede tardar más)</span>
        @endif


        {{-- Loader muy simple --}}
        <div id="page-loader" class="hidden mb-4 text-sm text-gray-600">
            ⏳ Cargando la siguiente página...
        </div>

        {{-- Paginación compacta ARRIBA --}}
        @if(isset($hotels) && $hotels instanceof \Illuminate\Pagination\LengthAwarePaginator && $hotels->hasPages())
        <div class="mb-6 flex items-center justify-between gap-3">
            <div class="text-sm text-gray-600">
                Mostrando
                <strong>{{ $hotels->firstItem() ?? 0 }}</strong>–<strong>{{ $hotels->lastItem() ?? 0 }}</strong>
                de <strong>{{ $hotels->total() }}</strong>
                {{ filled(request('hotel_codes')) ? 'hoteles (códigos manuales)' : 'hoteles (paginación nativa)' }}
                <span class="text-gray-400">| Disponibles en esta página: <strong>{{ $hotels->count() }}</strong></span>
            </div>

            <div class="flex items-center gap-2">
                @php
                $prevUrl = $hotels->appends(request()->except('page'))->previousPageUrl();
                $nextUrl = $hotels->appends(request()->except('page'))->nextPageUrl();
                @endphp

                @if(!$hotels->onFirstPage())
                <a href="{{ $prevUrl ? $prevUrl . '#top' : '#' }}"
                    class="px-3 py-1.5 border rounded-lg text-sm text-blue-700 hover:bg-blue-50 page-link">
                    « Anterior
                </a>
                @else
                <span class="px-3 py-1.5 border rounded-lg text-sm text-gray-400 cursor-not-allowed">« Anterior</span>
                @endif

                <span class="px-3 py-1.5 text-sm text-gray-600">
                    Página <strong>{{ $hotels->currentPage() }}</strong> / {{ $hotels->lastPage() }}
                </span>

                @if($hotels->hasMorePages())
                <a href="{{ $nextUrl ? $nextUrl . '#top' : '#' }}"
                    class="px-3 py-1.5 border rounded-lg text-sm text-blue-700 hover:bg-blue-50 page-link">
                    Siguiente »
                </a>
                @else
                <span class="px-3 py-1.5 border rounded-lg text-sm text-gray-400 cursor-not-allowed">Siguiente »</span>
                @endif
            </div>
        </div>
        @endif

        @isset($hotels)
        <div class="mb-8">
            <p class="text-gray-700 text-lg">
                🔎 Se encontraron
                <strong>
                    {{ $hotels instanceof \Illuminate\Pagination\LengthAwarePaginator ? $hotels->total() : count($hotels) }}
                </strong>
                hoteles disponibles con
                <strong>{{ $totalRooms }}</strong> habitaciones en total.
            </p>
            <p class="text-gray-600 text-sm mt-2">
                💼 Tarifas internas: <strong>{{ $internalRateCount }}</strong> |
                🌐 Tarifas externas: <strong>{{ $externalRateCount }}</strong> |
            </p>
        </div>
        @endisset

        {{-- ======== RESUMENES EN COLUMNAS ======== --}}
        @if(!empty($providerRateCounts) || !empty($providerHotelCounts) || !empty($providerHotelCountsPage))
        <div class="mt-6 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
            @if(!empty($providerRateCounts))
            <div class="card">
                <p class="text-gray-700 text-sm font-semibold mb-2">📦 Tarifas por proveedor</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    @foreach($providerRateCounts as $provider => $count)
                    @php $provUpper = strtoupper($provider); $isIntProv = in_array($provUpper, $internalCodtous, true); @endphp
                    <li>
                        <strong>{{ $provider }}</strong>: {{ $count }} tarifas
                        @if($isIntProv)
                        <span class="badge badge-internal ml-2">Internas</span>
                        @endif
                    </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(!empty($providerHotelCounts))
            <div class="card">
                <p class="text-gray-700 text-sm font-semibold mb-2">🏨 Hoteles por proveedor (bloque actual)</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    @foreach($providerHotelCounts as $provider => $count)
                    <li><strong>{{ $provider }}</strong>: {{ $count }} hoteles</li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if(!empty($providerHotelCountsPage))
            <div class="card">
                <p class="text-gray-700 text-sm font-semibold mb-2">🏨 Hoteles por proveedor (página actual)</p>
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    @foreach($providerHotelCountsPage as $provider => $count)
                    <li><strong>{{ $provider }}</strong>: {{ $count }} hoteles</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif


        @if(!empty($hotelRateCounts))
        <div class="mt-6">
            <p class="text-gray-700 text-sm font-semibold mb-2">🏨 Tarifas por hotel (bloque actual)</p>

            @php
            $cols = 2; // cambia a 3 si quieres 3 columnas
            $size = (int) ceil(max(1, count($hotelRateCounts)) / $cols);
            $hotelChunks = array_chunk($hotelRateCounts, $size, true);
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($hotelChunks as $chunk)
                <ul class="list-disc list-inside text-sm text-gray-700 space-y-1">
                    @foreach($chunk as $code => $row)
                    <li>
                        <strong>{{ $row['name'] ?? $code }}</strong> ({{ $code }}): {{ $row['count'] ?? 0 }} tarifas
                    </li>
                    @endforeach
                </ul>
                @endforeach
            </div>
        </div>
        @endif



        {{-- META de la respuesta del proveedor --}}
        @if(!empty($httpMeta))
        <div class="mb-8 grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-3">
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">⏱ Tiempo de respuesta</p>
                <p class="text-lg font-semibold text-blue-800">
                    {{ number_format($httpMeta['elapsed_ms'] / 1000, 2) }} s
                    <span class="text-xs text-gray-500">({{ $httpMeta['elapsed_ms'] }} ms)</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">Tiempo total desde que se envía la petición hasta recibir la última respuesta.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">📦 Tamaño procesado (descomprimido)</p>
                <p class="text-lg font-semibold text-blue-800">
                    @php $kb = $httpMeta['size_decompressed'] / 1024; @endphp
                    {{ number_format($kb, 2) }} KB
                    <span class="text-xs text-gray-500">({{ number_format($httpMeta['size_decompressed']) }} B)</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">Peso real de los datos XML tras descomprimir.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🧾 Status</p>
                <p class="text-lg font-semibold text-blue-800">
                    {{ $httpMeta['status'] }}
                    <span class="text-xs text-gray-500">{{ $httpMeta['content_type'] }}</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">Código HTTP y tipo de contenido recibido.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🗜️ Content-Encoding</p>
                <p class="text-lg font-semibold text-blue-800">
                    {{ $httpMeta['content_encoding'] }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Método de compresión usado (gzip, br, etc.).</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🔁 Transfer-Encoding</p>
                <p class="text-lg font-semibold text-blue-800">
                    {{ $httpMeta['transfer_encoding'] }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Forma en la que el servidor envía los datos (ej. chunked).</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🌐 Server / Connection</p>
                <p class="text-sm font-medium text-blue-800">
                    {{ $httpMeta['server'] ?? '—' }}
                    <span class="text-gray-500">| {{ $httpMeta['connection'] ?? '—' }}</span>
                </p>
                @if(!empty($httpMeta['date']))
                <p class="text-xs text-gray-500 mt-1">Date: {{ $httpMeta['date'] }}</p>
                @endif
                <p class="text-xs text-gray-500 mt-1">Servidor que respondió y estado de la conexión.</p>
            </div>

            @if($httpMeta['content_length'] !== '—')
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🚚 Tamaño transferido (Content-Length)</p>
                @php $len = (int) $httpMeta['content_length']; $kbl = $len / 1024; @endphp
                <p class="text-lg font-semibold text-blue-800">
                    {{ number_format($kbl, 2) }} KB
                    <span class="text-xs text-gray-500">({{ number_format($len) }} B)</span>
                </p>
                <p class="text-xs text-gray-500 mt-1">Tamaño en bytes indicado por el servidor (sin procesar).</p>
            </div>
            @endif

            {{-- Tiempos de red --}}
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">📡 Tiempos de red (cURL)</p>
                <ul class="text-sm text-blue-900 mt-1 space-y-1">
                    <li>TTFB: <strong>{{ $httpMeta['ttfb_ms'] ?? '—' }} ms</strong> <span class="text-gray-500 text-xs">Tiempo hasta recibir el primer byte.</span></li>
                    <li>Descarga: <strong>{{ $httpMeta['download_ms'] ?? '—' }} ms</strong> <span class="text-gray-500 text-xs">Tiempo en descargar los datos tras el primer byte.</span></li>
                    <li>Conexión: <strong>{{ $httpMeta['connect_ms'] ?? '—' }} ms</strong> <span class="text-gray-500 text-xs">Tiempo en establecer la conexión TCP.</span></li>
                    <li>TLS: <strong>{{ $httpMeta['ssl_ms'] ?? '—' }} ms</strong> <span class="text-gray-500 text-xs">Tiempo en el handshake SSL/TLS.</span></li>
                    <li>DNS: <strong>{{ $httpMeta['namelookup_ms'] ?? '—' }} ms</strong> <span class="text-gray-500 text-xs">Tiempo en resolver el nombre de dominio.</span></li>
                    <li>Total cURL: <strong>{{ $httpMeta['total_ms'] ?? '—' }} ms</strong> <span class="text-gray-500 text-xs">Tiempo total medido por cURL.</span></li>
                </ul>
                <p class="text-xs text-gray-500 mt-2">IP destino: {{ $httpMeta['primary_ip'] ?? '—' }}</p>
            </div>
        </div>
        @endif

        @if(!empty($perf ?? null))
        @php
        $p = is_array($perf) ? $perf : [];
        @endphp
        <div class="mb-8 grid grid-cols-1 md:grid-cols-3 xl:grid-cols-6 gap-3">
            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🗄️ BD (paginación)</p>
                <p class="text-lg font-semibold text-blue-800">{{ $p['db_ms'] ?? '—' }} ms</p>
                <p class="text-xs text-gray-500 mt-1">Tiempo en obtener los hoteles de la base de datos.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🌐 HTTP (pool)</p>
                <p class="text-lg font-semibold text-blue-800">{{ $p['http_ms'] ?? '—' }} ms</p>
                <p class="text-xs text-gray-500 mt-1">Tiempo total de las peticiones al proveedor.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🧩 Parseo XML</p>
                <p class="text-lg font-semibold text-blue-800">{{ $p['parse_ms'] ?? '—' }} ms</p>
                <p class="text-xs text-gray-500 mt-1">Tiempo en convertir el XML en datos PHP.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🧮 Agregación</p>
                <p class="text-lg font-semibold text-blue-800">{{ $p['aggregate_ms'] ?? '—' }} ms</p>
                <p class="text-xs text-gray-500 mt-1">Tiempo en combinar y preparar los datos para la vista.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🧠 Memoria pico</p>
                <p class="text-lg font-semibold text-blue-800">{{ $p['peak_mem_mb'] ?? '—' }} MiB</p>
                <p class="text-xs text-gray-500 mt-1">Memoria máxima usada durante el proceso.</p>
            </div>

            <div class="bg-white border border-gray-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs text-gray-500">🔚 Total app</p>
                <p class="text-lg font-semibold text-blue-800">{{ $p['total_ms'] ?? '—' }} ms</p>
                <p class="text-xs text-gray-500 mt-1">
                    Tiempo total de la aplicación.<br>
                    Hoteles: {{ $p['hotels_page'] ?? '—' }} · Hab: {{ $p['rooms_page'] ?? '—' }}
                </p>
            </div>
        </div>
        @endif




        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-8">
    @forelse ($hotels as $hotel)

        {{-- 1) Calcular N y el "Desde" ANTES de renderizar nada --}}
        @php
            // ¿Cuántas habitaciones pidió el usuario?
            $roomsReq = request()->has('rooms') ? (array) request('rooms') : [];
            $roomsEffLocal = is_array($roomsEff ?? null) ? $roomsEff : $roomsReq;
            $requestedRoomsCount = max(0, (int) collect($roomsEffLocal)->count());
            $N = max(1, min(4, $requestedRoomsCount));

            // Datos base
            $currency = $hotel['currency'] ?? 'EUR';
            $roomsAll = collect($hotel['rooms'] ?? []);

            // Helpers
            $toFloat = function ($v) {
                if (is_null($v)) return null;
                if (is_numeric($v)) return (float) $v;
                if (is_string($v)) {
                    $v = preg_replace('/[^0-9,.\-]/', '', $v);
                    if (strpos($v, ',') !== false && strpos($v, '.') !== false) $v = str_replace(',', '', $v);
                    else $v = str_replace(',', '.', $v);
                    return is_numeric($v) ? (float) $v : null;
                }
                return null;
            };
            $afterHash = function ($v) { if (!is_string($v) || $v==='') return ''; $pos=strpos($v,'#'); return $pos===false?trim($v):trim(substr($v,$pos+1)); };
            $removeSuffix = function ($text,$suffix){ $len=strlen($suffix); return $len>0 && substr($text,-$len)===$suffix?substr($text,0,-$len):$text; };
            $norm = function ($s){ $s=is_string($s)?trim($s):''; $s=preg_replace('/\s+/',' ',$s); return mb_strtolower($s); };
            $getRefdis = function(array $r){ if (isset($r['refdis']) && is_numeric($r['refdis'])) return (int)$r['refdis']; $ic=(string)($r['infrcl']??$r['infrcl_text']??''); $head=strtok($ic,'!~'); return is_numeric($head)?(int)$head:null; };

            // ---- Precio a mostrar en "Desde" ----
            $minDisplayPrice = null;

            if ($N === 1) {
                $minDisplayPrice = $roomsAll
                    ->pluck('price_per_night')
                    ->map($toFloat)
                    ->filter(fn($x) => $x !== null && $x > 0)
                    ->min();
            } else {
                $groups = [];
                foreach ($roomsAll as $r) {
                    $codtou = strtoupper((string)($r['codtou'] ?? ''));
                    $rawSmo = $r['codsmo'] ?? ($r['room_type'] ?? '');
                    $rawCha = $r['codcha'] ?? ($r['room_code'] ?? '');
                    $rawRal = $r['codral'] ?? ($r['board'] ?? '');
                    $infrcl = $r['infrcl'] ?? ($r['infrcl_text'] ?? '');

                    $smoLabel = $afterHash($rawSmo);
                    $chaLabel = $afterHash($rawCha);
                    $ralLabel = $afterHash($rawRal);

                    if ($chaLabel === '' && is_string($infrcl) && $infrcl !== '') {
                        $parts = explode('!~', $infrcl);
                        $last = trim(end($parts));
                        $first = strtok($last, '_');
                        if ($first !== false) { $first = $removeSuffix($first, 'Room'); $chaLabel = trim($first); }
                    }

                    $roomParts = [];
                    if ($smoLabel !== '') $roomParts[] = $smoLabel;
                    if ($chaLabel !== '' && stripos($smoLabel, $chaLabel) === false) $roomParts[] = $chaLabel;

                    $roomDesc = count($roomParts) ? implode(' ', $roomParts) : '—';
                    $boardDesc = $ralLabel !== '' ? $ralLabel : '—';

                    $ref = $getRefdis($r);
                    if (!is_int($ref) || $ref < 1 || $ref > $N) continue;

                    $key = $codtou.'|'.$norm($roomDesc).'|'.$norm($boardDesc);
                    if (!isset($groups[$key])) $groups[$key] = ['refs'=>[]];
                    $groups[$key]['refs'][$ref] = $r;
                }

                $packTotals = [];
                foreach ($groups as $g) {
                    $complete = true; $total = 0.0;
                    for ($i=1; $i <= $N; $i++) {
                        if (empty($g['refs'][$i])) { $complete=false; break; }
                        $px = $toFloat($g['refs'][$i]['price_per_night'] ?? null);
                        if ($px === null || $px <= 0) { $complete=false; break; }
                        $total += $px;
                    }
                    if ($complete) $packTotals[] = $total;
                }
                $minDisplayPrice = $packTotals ? min($packTotals) : null;
            }
        @endphp

        {{-- 2) Si NO hay habitaciones o el "Desde" es 0/nulo, saltar ANTES de abrir la card --}}
        @if ($roomsAll->isEmpty() || $minDisplayPrice === null || $minDisplayPrice <= 0)
            @continue
        @endif

        {{-- 3) A partir de aquí pintamos la card con seguridad --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow hover:shadow-lg transition p-6 flex flex-col justify-between">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-blue-800 mb-1">{{ $hotel['name'] }}</h2>
                <p class="text-sm text-gray-500 mb-1">⭐ Categoría: {{ $hotel['category'] }}</p>
                <p class="text-sm text-gray-500">🏷 Código: {{ $hotel['code'] }} | 📍 Zona: {{ $hotel['zone'] }}</p>
            </div>

            {{-- 📦 Tarifas por proveedor --}}
            @php
                $internalCodtous = array_map('strtoupper', config('itravex.internal_codtous', ['LIB']));
                $provCounts = collect($hotel['rooms'] ?? [])
                    ->map(fn($r) => $r['codtou'] ?? '')
                    ->filter()
                    ->countBy()
                    ->sortDesc()
                    ->toArray();
            @endphp

            @if(!empty($provCounts))
                <div class="card mb-4">
                    <p class="text-gray-700 text-sm font-semibold mb-2">📦 Tarifas por proveedor</p>
                    <ul class="list-disc list-inside text-sm text-gray-700 space-y-0.5">
                        @foreach($provCounts as $prov => $cnt)
                            @php $provUpper = strtoupper($prov); $isIntProv = in_array($provUpper, $internalCodtous, true); @endphp
                            <li>
                                <strong>{{ $prov }}</strong>: {{ $cnt }} tarifas
                                @if($isIntProv)
                                    <span class="badge badge-internal ml-2">Interna</span>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Detalle de habitaciones / packs --}}
            <div class="mb-4">
                <details class="group">
                    @php
                    // 1) Todas las habitaciones del hotel ordenadas por precio:
                    $roomsAll = collect($hotel['rooms'] ?? []);
                    $roomsSorted = $roomsAll->sortBy(function ($r) {
                    $p = $r['price_per_night'] ?? null;
                    return is_numeric($p) ? (float)$p : INF;
                    })->values();

                    // === Preparación de ocupación y helpers ===
                    $roomsReq = request()->has('rooms') ? (array) request('rooms') : [];
                    $roomsEffLocal = is_array($roomsEff ?? null) ? $roomsEff : $roomsReq;
                    $requestedRoomsCount = max(0, (int) collect($roomsEffLocal)->count());
                    $N = max(1, min(4, $requestedRoomsCount));

                    $afterHash = function ($v) { if (!is_string($v) || $v==='') return ''; $pos=strpos($v,'#'); return $pos===false?trim($v):trim(substr($v,$pos+1)); };
                    $removeSuffix = function ($text,$suffix){ $len=strlen($suffix); return $len>0 && substr($text,-$len)===$suffix?substr($text,0,-$len):$text; };
                    $norm = function ($s){ $s=is_string($s)?trim($s):''; $s=preg_replace('/\s+/',' ',$s); return mb_strtolower($s); };
                    $getRefdis = function(array $r){ if (isset($r['refdis']) && is_numeric($r['refdis'])) return (int)$r['refdis']; $ic=(string)($r['infrcl']??$r['infrcl_text']??''); $head=strtok($ic,'!~'); return is_numeric($head)?(int)$head:null; };
                    $internalCodtous = array_map('strtoupper', config('itravex.internal_codtous', ['LIB']));

                    // 2) Packs solo si N>=2
                    $packs = [];
                    if ($N >= 2) {
                    $groups = [];
                    foreach ($roomsSorted as $r) {
                    $codtou = strtoupper((string)($r['codtou'] ?? ''));
                    $rawSmo = $r['codsmo'] ?? ($r['room_type'] ?? '');
                    $rawCha = $r['codcha'] ?? ($r['room_code'] ?? '');
                    $rawRal = $r['codral'] ?? ($r['board'] ?? '');
                    $infrcl = $r['infrcl'] ?? ($r['infrcl_text'] ?? '');

                    $smoLabel = $afterHash($rawSmo);
                    $chaLabel = $afterHash($rawCha);
                    $ralLabel = $afterHash($rawRal);

                    if ($chaLabel === '' && is_string($infrcl) && $infrcl !== '') {
                    $parts = explode('!~', $infrcl);
                    $last = trim(end($parts));
                    $first = strtok($last, '_');
                    if ($first !== false) { $first = $removeSuffix($first, 'Room'); $chaLabel = trim($first); }
                    }

                    $roomParts = [];
                    if ($smoLabel !== '') $roomParts[] = $smoLabel;
                    if ($chaLabel !== '' && stripos($smoLabel, $chaLabel) === false) $roomParts[] = $chaLabel;

                    $roomDesc = count($roomParts) ? implode(' ', $roomParts) : '—';
                    $boardDesc = $ralLabel !== '' ? $ralLabel : '—';

                    $ref = $getRefdis($r);
                    if (!is_int($ref) || $ref < 1 || $ref> $N) continue;

                        $key = $codtou.'|'.$norm($roomDesc).'|'.$norm($boardDesc);
                        if (!isset($groups[$key])) $groups[$key] = ['prov'=>$codtou,'desc'=>$roomDesc,'board'=>$boardDesc,'refs'=>[]];
                        $groups[$key]['refs'][$ref] = $r;
                        }

                        foreach ($groups as $g) {
                        $complete = true; $total = 0.0;
                        for ($i=1; $i<=$N; $i++) {
                            if (empty($g['refs'][$i])) { $complete=false; break; }
                            $total +=(float)($g['refs'][$i]['price_per_night'] ?? 0);
                            }
                            if ($complete) $packs[]=['prov'=>$g['prov'],'desc'=>$g['desc'],'board'=>$g['board'],'refs'=>$g['refs'],'total'=>$total];
                            }
                            usort($packs, fn($a,$b) => $a['total'] <=> $b['total']);
                                }

                                // 3) Contador para el summary
                                $availableCount = ($N === 1) ? $roomsSorted->count() : count($packs);
                                @endphp

                                <summary class="flex items-center justify-between cursor-pointer select-none">
                                    <h3 class="text-base font-semibold text-gray-700">Habitaciones</h3>
                                    <span class="inline-flex items-center gap-2 text-sm text-indigo-600">
                                        {{ $availableCount }} disponibles
                                        <svg class="h-4 w-4 transition-transform group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                                        </svg>
                                    </span>
                                </summary>

                                @php
                                $roomsReq = request()->has('rooms') ? (array) request('rooms') : [];
                                $roomsEffLocal = is_array($roomsEff ?? null) ? $roomsEff : $roomsReq;
                                $requestedRoomsCount = max(0, (int) collect($roomsEffLocal)->count());
                                $N = max(1, min(4, $requestedRoomsCount));

                                $afterHash = function ($v) { if (!is_string($v) || $v==='') return ''; $pos=strpos($v,'#'); return $pos===false?trim($v):trim(substr($v,$pos+1)); };
                                $removeSuffix = function ($text,$suffix){ $len=strlen($suffix); return $len>0 && substr($text,-$len)===$suffix?substr($text,0,-$len):$text; };
                                $norm = function ($s){ $s=is_string($s)?trim($s):''; $s=preg_replace('/\s+/',' ',$s); return mb_strtolower($s); };
                                $getRefdis = function(array $r){ if (isset($r['refdis']) && is_numeric($r['refdis'])) return (int)$r['refdis']; $ic=(string)($r['infrcl']??$r['infrcl_text']??''); $head=strtok($ic,'!~'); return is_numeric($head)?(int)$head:null; };
                                $internalCodtous = array_map('strtoupper', config('itravex.internal_codtous', ['LIB']));
                                @endphp

                                <ul class="space-y-2 mt-3">
                                    @if ($N === 1)
                                    @forelse ($roomsSorted as $r)
                                    @php
                                    $codtou = strtoupper((string)($r['codtou'] ?? ''));
                                    $isInternal = $codtou !== '' && in_array($codtou, $internalCodtous, true);
                                    $rawSmo = $r['codsmo'] ?? ($r['room_type'] ?? '');
                                    $rawCha = $r['codcha'] ?? ($r['room_code'] ?? '');
                                    $rawRal = $r['codral'] ?? ($r['board'] ?? '');
                                    $infrcl = $r['infrcl'] ?? ($r['infrcl_text'] ?? '');

                                    $smoLabel = $afterHash($rawSmo);
                                    $chaLabel = $afterHash($rawCha);
                                    $ralLabel = $afterHash($rawRal);

                                    if ($chaLabel === '' && is_string($infrcl) && $infrcl !== '') {
                                    $parts = explode('!~', $infrcl);
                                    $last = trim(end($parts));
                                    $first = strtok($last, '_');
                                    if ($first !== false) { $first = $removeSuffix($first, 'Room'); $chaLabel = trim($first); }
                                    }

                                    $roomParts = [];
                                    if ($smoLabel !== '') $roomParts[] = $smoLabel;
                                    if ($chaLabel !== '' && stripos($smoLabel, $chaLabel) === false) $roomParts[] = $chaLabel;

                                    $roomDesc = count($roomParts) ? implode(' ', $roomParts) : '—';
                                    $boardDesc = $ralLabel !== '' ? $ralLabel : '—';
                                    $price = (float) ($r['price_per_night'] ?? 0);
                                    @endphp

                                    <li class="room-item {{ $isInternal ? 'is-internal' : '' }}">
                                        @if($isInternal)<span class="flag-internal">INTERNA</span>@endif

                                        <div class="room-left">
                                            <p class="room-title"><span class="font-semibold text-slate-700">Tipo —</span> {{ $roomDesc }}</p>
                                            <p class="room-title"><span class="font-semibold text-slate-700">Régimen —</span> {{ $boardDesc }}</p>
                                            @if(!empty($codtou))
                                            <p class="mt-1 text-xs text-slate-600">
                                                🏷️ Proveedor: <span class="font-semibold text-slate-800">{{ $codtou }}</span>
                                                @if($isInternal) <span class="badge badge-internal ml-2">Interna</span> @endif
                                            </p>
                                            @endif
                                        </div>

                                        <div class="room-right">
                                            <div class="room-price">{{ number_format($price, 2) }} {{ $hotel['currency'] ?? 'EUR' }}</div>
                                           <form method="GET" action="{{ route('availability.lock.form') }}" class="inline">
    <input type="hidden" name="hotel_name" value="{{ $hotel['name'] }}">
    <input type="hidden" name="hotel_code" value="{{ $hotel['code'] }}">
    <input type="hidden" name="currency" value="{{ $hotel['currency'] ?? 'EUR' }}">
    <input type="hidden" name="start_date" value="{{ request('fecini') }}">
    <input type="hidden" name="end_date" value="{{ request('fecfin') }}">
    <input type="hidden" name="room_type" value="{{ $roomDesc }}">
    <input type="hidden" name="board" value="{{ $boardDesc }}">
    <input type="hidden" name="provider" value="{{ $codtou }}">
    <input type="hidden" name="price_per_night" value="{{ $price }}">
    <input type="hidden" name="hotel_internal_id" value="{{ $hotel['hotel_internal_id'] ?? '' }}">
    <input type="hidden" name="room_internal_id" value="{{ $r['room_internal_id'] ?? '' }}">

    {{-- === distri[1] también para simple === --}}
    @php
        // 1) Si vienes de multi, usa la 1ª ocupación del array rooms/effective
        $roomsEffLocal = is_array($rooms ?? null)
            ? $rooms
            : (is_array($effective['rooms'] ?? null) ? $effective['rooms'] : []);

        $occ1 = $roomsEffLocal[0] ?? null;

        // 2) Fallback para formularios antiguos (top-level)
        if (!$occ1) {
            $occ1 = [
                'adl'  => (int) (request('numadl') ?? request('adl') ?? request('adults') ?? 2),
                'chd'  => (int) (request('numnin') ?? request('numchd') ?? request('chd') ?? request('children') ?? 0),
                'ages' => (array) request()->input('ages', request()->input('edades', [])),
            ];
        }

        $adl1 = (int) ($occ1['adl'] ?? 2);
        $chd1 = (int) ($occ1['chd'] ?? 0);
        $ages1 = array_values(array_filter((array)($occ1['ages'] ?? []), fn($a) => $a !== '' && $a !== null));
    @endphp

    <input type="hidden" name="distri[1][numadl]" value="{{ $adl1 }}">
    <input type="hidden" name="distri[1][numnin]" value="{{ $chd1 }}">
    @foreach ($ages1 as $j => $age)
        <input type="hidden" name="distri[1][edanin][{{ $j }}]" value="{{ (int)$age }}">
    @endforeach
    {{-- (opcional) conserva rooms[...] si lo usas en otros sitios --}}
    @if (request()->has('rooms'))
        @foreach ((array) request('rooms') as $ri => $rr)
            <input type="hidden" name="rooms[{{ $ri }}][adl]" value="{{ (int)($rr['adl'] ?? 1) }}">
            <input type="hidden" name="rooms[{{ $ri }}][chd]" value="{{ (int)($rr['chd'] ?? 0) }}">
            @foreach ((array)($rr['ages'] ?? []) as $ai => $age)
                <input type="hidden" name="rooms[{{ $ri }}][ages][{{ $ai }}]" value="{{ (int)$age }}">
            @endforeach
        @endforeach
    @endif

    <button type="submit" class="btn-sel select-btn">
        <span class="spinner" aria-hidden="true"></span>
        <span class="label">Seleccionar</span>
    </button>
</form>

                                        </div>
                                    </li>
                                    @empty
                                    <li class="text-sm text-slate-600">No hay habitaciones disponibles.</li>
                                    @endforelse
                                    @else
                                    @php
                                    $groups = [];
                                    foreach ($roomsSorted as $r) {
                                    $codtou = strtoupper((string)($r['codtou'] ?? ''));
                                    $rawSmo = $r['codsmo'] ?? ($r['room_type'] ?? '');
                                    $rawCha = $r['codcha'] ?? ($r['room_code'] ?? '');
                                    $rawRal = $r['codral'] ?? ($r['board'] ?? '');
                                    $infrcl = $r['infrcl'] ?? ($r['infrcl_text'] ?? '');

                                    $smoLabel = $afterHash($rawSmo);
                                    $chaLabel = $afterHash($rawCha);
                                    $ralLabel = $afterHash($rawRal);

                                    if ($chaLabel === '' && is_string($infrcl) && $infrcl !== '') {
                                    $parts = explode('!~', $infrcl);
                                    $last = trim(end($parts));
                                    $first = strtok($last, '_');
                                    if ($first !== false) { $first = $removeSuffix($first, 'Room'); $chaLabel = trim($first); }
                                    }

                                    $roomParts = [];
                                    if ($smoLabel !== '') $roomParts[] = $smoLabel;
                                    if ($chaLabel !== '' && stripos($smoLabel, $chaLabel) === false) $roomParts[] = $chaLabel;

                                    $roomDesc = count($roomParts) ? implode(' ', $roomParts) : '—';
                                    $boardDesc = $ralLabel !== '' ? $ralLabel : '—';

                                    $ref = $getRefdis($r);
                                    if (!is_int($ref) || $ref < 1 || $ref> $N) continue;

                                        $key = $codtou.'|'.$norm($roomDesc).'|'.$norm($boardDesc);
                                        if (!isset($groups[$key])) $groups[$key] = ['prov'=>$codtou,'desc'=>$roomDesc,'board'=>$boardDesc,'refs'=>[]];
                                        $groups[$key]['refs'][$ref] = $r;
                                        }
                                        $packs = [];
                                        foreach ($groups as $g) {
                                        $complete = true; $total = 0.0;
                                        for ($i=1; $i<=$N; $i++) {
                                            if (empty($g['refs'][$i])) { $complete=false; break; }
                                            $total +=(float)($g['refs'][$i]['price_per_night'] ?? 0);
                                            }
                                            if ($complete) $packs[]=['prov'=>$g['prov'],'desc'=>$g['desc'],'board'=>$g['board'],'refs'=>$g['refs'],'total'=>$total];
                                            }
                                            usort($packs, fn($a,$b) => $a['total'] <=> $b['total']);
                                                @endphp

                                                @forelse ($packs as $pack)
                                                <li class="room-pack rounded-xl border border-slate-200 bg-white p-5 shadow-sm hover:shadow-md transition">
                                                    <div class="pack-head">
                                                        <div class="min-w-0 space-y-2 text-slate-700 desc">
                                                            <p class="text-sm"><span class="font-semibold">Tipo —</span> <span>{{ $pack['desc'] }}</span></p>
                                                            <p class="text-sm"><span class="font-semibold">Régimen —</span> <span>{{ $pack['board'] }}</span></p>
                                                            @php $prov = $pack['prov'] ?? '—'; @endphp
                                                            <div class="flex items-center gap-2 text-xs">
                                                                <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2 py-0.5 ring-1 ring-amber-200 text-amber-800">Proveedor</span>
                                                                <span class="font-semibold text-slate-900">{{ $prov }}</span>
                                                            </div>
                                                        </div>

                                                        <div class="shrink-0 flex flex-col items-stretch md:items-end gap-2">
                                                            <div class="text-right">
                                                                <div class="text-2xl font-semibold text-slate-900">{{ number_format($pack['total'], 2) }} {{ $hotel['currency'] ?? 'EUR' }}</div>
                                                                <div class="text-xs text-slate-500">Total {{ $N }} habitaciones</div>
                                                            </div>

                                                            <form method="GET" action="{{ route('availability.lock.form') }}" class="w-full md:w-auto">
    <input type="hidden" name="hotel_name" value="{{ $hotel['name'] }}">
    <input type="hidden" name="hotel_code" value="{{ $hotel['code'] }}">
    <input type="hidden" name="currency" value="{{ $hotel['currency'] ?? 'EUR' }}">
    <input type="hidden" name="start_date" value="{{ request('fecini') }}">
    <input type="hidden" name="end_date" value="{{ request('fecfin') }}">
    <input type="hidden" name="room_type" value="{{ $pack['desc'] }}">
    <input type="hidden" name="board" value="{{ $pack['board'] }}">
    <input type="hidden" name="provider" value="{{ $prov }}">
    <input type="hidden" name="price_total" value="{{ $pack['total'] }}">
    <input type="hidden" name="hotel_internal_id" value="{{ $hotel['hotel_internal_id'] ?? '' }}">

    {{-- refs del pack --}}
    @foreach ($pack['refs'] as $i => $rr)
        <input type="hidden" name="pack[{{ $i }}][room_internal_id]" value="{{ $rr['room_internal_id'] ?? '' }}">
        <input type="hidden" name="pack[{{ $i }}][refdis]" value="{{ $rr['refdis'] ?? '' }}">
        <input type="hidden" name="pack[{{ $i }}][price_per_night]" value="{{ (float)($rr['price_per_night'] ?? 0) }}">
    @endforeach

    {{-- Ocupación por habitación hacia el lock --}}
    @php
      $roomsEffLocal = is_array($rooms ?? null)
          ? $rooms
          : (is_array($effective['rooms'] ?? null) ? $effective['rooms'] : []);
    @endphp
    @foreach ($roomsEffLocal as $i => $occ)
        <input type="hidden" name="distri[{{ $i+1 }}][numadl]" value="{{ (int)($occ['adl'] ?? 2) }}">
        <input type="hidden" name="distri[{{ $i+1 }}][numnin]" value="{{ (int)($occ['chd'] ?? 0) }}">
        @if(!empty($occ['ages']) && is_array($occ['ages']))
            @foreach ($occ['ages'] as $j => $age)
                <input type="hidden" name="distri[{{ $i+1 }}][edanin][{{ $j }}]" value="{{ (int)$age }}">
            @endforeach
        @endif
    @endforeach

    <button type="submit" class="btn-sel select-btn">
        <span class="spinner" aria-hidden="true"></span>
        <span class="label">Seleccionar pack</span>
    </button>
</form>
                                                        </div>
                                                    </div>

                                                    <div class="mt-4 flex flex-wrap gap-2 rooms-grid">
                                                        @foreach ($pack['refs'] as $i => $r)
                                                        @php $ref = isset($r['refdis']) ? (int)$r['refdis'] : ($getRefdis($r) ?? null); $p = (float)($r['price_per_night'] ?? 0); @endphp
                                                        <div class="room-chip">
                                                            <div class="flex items-center justify-between gap-3">
                                                                <div class="text-xs font-medium text-slate-600">Hab {{ $i }}</div>
                                                                <div class="text-[11px] rounded-full bg-slate-100 px-2 py-0.5 text-slate-700">refdis {{ $ref ?? '—' }}</div>
                                                            </div>
                                                            <div class="mt-1 text-sm font-semibold text-slate-900">{{ number_format($p, 2) }} {{ $hotel['currency'] ?? 'EUR' }}</div>
                                                        </div>
                                                        @endforeach
                                                    </div>
                                                </li>
                                                @empty
                                                <li class="text-sm text-slate-600">No hay packs de {{ $N }} habitaciones disponibles.</li>
                                                @endforelse
                                                @endif
                                </ul>
                </details>
            </div>

            {{-- Pie: "Desde" ya validado (> 0) --}}
            <div class="mt-auto">
                <p class="text-right text-lg font-bold text-green-600">
                    Desde {{ number_format($minDisplayPrice, 2) }} {{ $currency }}
                </p>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center text-gray-500 text-lg mt-10">
            ❌ No se encontraron hoteles para los parámetros seleccionados.
        </div>
        @endforelse
    </div>


    {{-- (SIN paginación abajo) --}}
    </div>
    @if(!empty($firstPayload))
    <div id="xml-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl shadow-lg max-w-3xl w-full p-6 relative">
            <button id="btn-close-xml" class="absolute top-3 right-3 text-gray-500 hover:text-gray-800">
                ✖
            </button>
            <h2 class="text-lg font-semibold text-slate-800 mb-4">📤 XML de la petición</h2>
            <pre class="bg-slate-50 border rounded p-3 text-xs overflow-auto max-h-[70vh] text-slate-700">{{ $firstPayload }}</pre>
        </div>
    </div>
    @endif
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Loader arriba al cambiar de página
            var loader = document.getElementById('page-loader');
            document.querySelectorAll('a.page-link').forEach(function(a) {
                a.addEventListener('click', function() {
                    if (loader) loader.classList.remove('hidden');
                });
            });

            // Estado de carga al enviar "Seleccionar"
            document.querySelectorAll('form[action*="availability.lock.form"]').forEach(function(form) {
                var btn = form.querySelector('button.select-btn');
                if (!btn) return;

                form.addEventListener('submit', function() {
                    // Evita doble submit, muestra spinner y deshabilita
                    btn.classList.add('is-loading');
                    btn.setAttribute('aria-busy', 'true');
                    btn.disabled = true;
                    // Si quieres mostrar el loader general también:
                    if (loader) loader.classList.remove('hidden');
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function() {
            var btn = document.getElementById('btn-show-xml');
            var modal = document.getElementById('xml-modal');
            var close = document.getElementById('btn-close-xml');
            if (btn && modal) {
                btn.addEventListener('click', () => modal.classList.remove('hidden'));
                close.addEventListener('click', () => modal.classList.add('hidden'));
                modal.addEventListener('click', e => {
                    if (e.target === modal) modal.classList.add('hidden');
                });
            }
        });
    </script>
    <style>
        .room-pack,
        .room-pack * {
            word-break: normal !important;
            overflow-wrap: anywhere;
            white-space: normal;
        }
    </style>
    <style>
        .pack-head {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
            gap: 1rem;
        }
    </style>




</x-app-layout>