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


@section('content')
{{-- Resumen de la petición enviada (usando valores efectivos) --}}
@php
$eff = $effective ?? [];
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
$numadlIn = request('numadl');
$codnacIn = strtoupper((string) request('codnac',''));
$timeoutIn = request('timeout');
$numrstIn = request('numrst');

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
            <dt class="font-medium text-slate-600">Adultos</dt>
            <dd>{{ $numadlIn ?: '—' }}</dd>
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
                <li><strong>{{ $provider }}</strong>: {{ $count }} tarifas</li>
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
        <div class="bg-white rounded-2xl border border-gray-200 shadow hover:shadow-lg transition p-6 flex flex-col justify-between">
            <div class="mb-4">
                <h2 class="text-xl font-bold text-blue-800 mb-1">{{ $hotel['name'] }}</h2>
                <p class="text-sm text-gray-500 mb-1">⭐ Categoría: {{ $hotel['category'] }}</p>
                <p class="text-sm text-gray-500">🏷 Código: {{ $hotel['code'] }} | 📍 Zona: {{ $hotel['zone'] }}</p>
            </div>
            <div class="mb-4">
                <details class="group">
                    <summary class="flex items-center justify-between cursor-pointer select-none">
                        <h3 class="text-base font-semibold text-gray-700">
                            Habitaciones
                        </h3>
                        <span class="inline-flex items-center gap-2 text-sm text-indigo-600">
                            {{ count($hotel['rooms']) }} disponibles
                            <svg class="h-4 w-4 transition-transform group-open:rotate-90" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 111.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    </summary>

                    <ul class="space-y-2 mt-3">
                        @foreach ($hotel['rooms'] as $room)
                        <li class="room-item">
                            <div class="room-left">
                                <p class="room-title">
                                    {{ $room['room_type'] ?: '—' }} <span class="text-slate-400">—</span> {{ $room['board'] ?: '—' }}
                                </p>
                                <p class="room-meta">
                                    💶 {{ number_format($room['price_per_night'], 2) }} {{ $hotel['currency'] }}
                                    @if ($room['availability'])
                                    <span class="ml-2 inline-flex items-center rounded-md bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        {{ $room['availability'] }} disp.
                                    </span>
                                    @endif
                                </p>
                            </div>

                            <div class="room-right">
                                <form method="GET" action="{{ route('availability.lock.form') }}" class="shrink-0">
                                    <input type="hidden" name="hotel_name" value="{{ $hotel['name'] }}">
                                    <input type="hidden" name="hotel_code" value="{{ $hotel['code'] }}">
                                    <input type="hidden" name="currency" value="{{ $hotel['currency'] }}">
                                    <input type="hidden" name="price_per_night" value="{{ $room['price_per_night'] }}">
                                    <input type="hidden" name="board" value="{{ $room['board'] }}">
                                    <input type="hidden" name="codzge" value="{{ $hotel['zone'] }}">
                                    <input type="hidden" name="start_date" value="{{ request('fecini') }}">
                                    <input type="hidden" name="end_date" value="{{ request('fecfin') }}">
                                    <input type="hidden" name="room_type" value="{{ $room['room_type'] }}">
                                    <input type="hidden" name="room_code" value="{{ $room['room_code'] }}">
                                    <input type="hidden" name="hotel_internal_id" value="{{ $hotel['hotel_internal_id'] }}">
                                    <input type="hidden" name="room_internal_id" value="{{ $room['room_internal_id'] }}">

                                    <button type="submit" class="btn-sel select-btn">
                                        <span class="label">Seleccionar</span>
                                        <span class="spinner" aria-hidden="true"></span>
                                    </button>
                                </form>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </details>
            </div>



            <div class="mt-auto">
                <p class="text-right text-lg font-bold text-green-600">
                    Desde {{ number_format($hotel['price'], 2) }} {{ $hotel['currency'] }}
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
</script>


</x-app-layout>