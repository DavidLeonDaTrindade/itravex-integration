<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use SimpleXMLElement;
use App\Models\Zone;
use Illuminate\Support\Facades\Log;
use App\Models\ItravexReservation;
use GuzzleHttp\TransferStats;
use App\Models\Hotel;
use GuzzleHttp\Client;
use GuzzleHttp\Promise\EachPromise;

class AvailabilityController extends Controller
{
    // ✅ NUEVO: normaliza y valida reglas de negocio de habitaciones (adl+chd <= 4)
    private function normalizeRooms(array $rooms): array
    {
        $out = [];
        foreach ($rooms as $i => $r) {
            $adl = max(0, (int)($r['adl'] ?? 0));
            $chd = max(0, (int)($r['chd'] ?? 0));
            $ages = array_values(array_filter(array_map(
                fn($v) => is_numeric($v) ? (int)$v : null,
                (array)($r['ages'] ?? [])
            ), fn($v) => $v !== null));

            // Reglas de negocio
            if ($adl < 1) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "rooms.$i.adl" => "Debe haber al menos 1 adulto en la habitación " . ($i + 1),
                ]);
            }
            if ($adl + $chd > 4) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "rooms.$i.chd" => "Máximo 4 personas por habitación en la habitación " . ($i + 1),
                ]);
            }
            if ($chd !== count($ages)) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "rooms.$i.ages" => "Indica exactamente $chd edad(es) de niño(s) en la habitación " . ($i + 1),
                ]);
            }
            foreach ($ages as $k => $age) {
                if ($age < 0 || $age > 17) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "rooms.$i.ages.$k" => "La edad del niño debe estar entre 0 y 17 en la habitación " . ($i + 1),
                    ]);
                }
            }

            $out[] = ['adl' => $adl, 'chd' => $chd, 'ages' => $ages];
        }
        if (empty($out)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                "rooms" => "Debes indicar al menos una habitación.",
            ]);
        }
        return $out;
    }

    // ✅ NUEVO: genera el XML <distri>…</distri> para todas las habitaciones
    private function buildDistriXml(array $rooms): string
    {
        $blocks = [];
        foreach ($rooms as $i => $r) {
            $id = $i + 1;
            $lines = [];
            $lines[] = "  <distri id=\"{$id}\">";
            $lines[] = "    <numuni>1</numuni>";
            $lines[] = "    <numadl>{$r['adl']}</numadl>";
            if ($r['chd'] > 0) {
                $lines[] = "    <numnin>{$r['chd']}</numnin>";
                foreach ($r['ages'] as $age) {
                    $lines[] = "    <edanin>{$age}</edanin>";
                }
            }
            $lines[] = "  </distri>";
            $blocks[] = implode("\n", $lines);
        }
        return implode("\n", $blocks);
    }

    public function checkAvailability(Request $request)
    {
        // === PRIMERA FASE: si llega POST, guardamos hotel_codes en cache y redirigimos a GET con 'k' (PRG) ===
        if ($request->isMethod('post')) {
            // Validación en POST (igual que la tuya)
            // 🔁 CAMBIO: validación POST (quitamos numadl y añadimos rooms[])
            $validated = $request->validate([
                'fecini'      => 'required|date',
                'fecfin'      => 'required|date|after_or_equal:fecini',
                'codzge'      => 'required_without:hotel_codes|string|nullable',
                'hotel_codes' => 'required_without:codzge|string|nullable',

                // ✅ multi-hab
                'rooms'          => 'required|array|min:1',
                'rooms.*.adl'    => 'required|integer|min:1|max:4',
                'rooms.*.chd'    => 'nullable|integer|min:0|max:3',
                'rooms.*.ages'   => 'nullable|array',
                'rooms.*.ages.*' => 'nullable|integer|min:0|max:17',

                'page'        => 'sometimes|integer|min:1',
                // modo eliminado, pero si aún llega lo ignoramos
                'mode'        => 'sometimes|in:full,fast',

                // Opcionales
                'timeout'     => 'nullable|integer|min:1000|max:60000',
                'numrst'      => 'nullable|integer|min:1|',
                'batch_size'  => 'nullable|integer|min:1|max:200',
                'per_page'    => 'sometimes|integer|min:1|max:500',
                'endpoint'    => 'sometimes|url|nullable',
                'codsys'      => 'sometimes|string|nullable',
                'codage'      => 'sometimes|string|nullable',
                'user'        => 'sometimes|string|nullable',
                'pass'        => 'sometimes|string|nullable',
                'codtou'      => 'sometimes|string|nullable',
                'codnac'      => 'nullable|string|min:2|max:3',

            ]);

            // ✅ normaliza y cachea rooms para la redirección (evita URLs largas)
            $rooms = $this->normalizeRooms($validated['rooms']);
            $rk = 'rooms:' . \Illuminate\Support\Str::uuid()->toString();
            cache()->put($rk, $rooms, now()->addHour());

            // ✅ hotel codes a cache (como ya tenías)
            $codesRaw = (string) ($validated['hotel_codes'] ?? '');
            $codes = [];
            if ($codesRaw !== '') {
                $codes = preg_split('/[\s,]+/u', trim($codesRaw), -1, PREG_SPLIT_NO_EMPTY);
                $codes = array_values(array_unique(array_map('trim', $codes)));
            }
            $k = 'hotlist:' . \Illuminate\Support\Str::uuid()->toString();
            cache()->put($k, $codes, now()->addHour());

            // ✅ Redirige a GET sin hotel_codes ni rooms, con k y rk
            return redirect()->route('availability.search', array_merge(
                $request->except(['_token', 'hotel_codes', 'rooms']),
                ['k' => $k, 'rk' => $rk]
            ));
        }

        // === SEGUNDA FASE: GET normal (con o sin 'k') ===
        // En GET permitimos que ni 'hotel_codes' ni 'codzge' estén si viene 'k'
        $validated = $request->validate([
            'fecini'      => 'required|date',
            'fecfin'      => 'required|date|after_or_equal:fecini',
            'k'           => 'sometimes|string|nullable',
            'rk'          => 'sometimes|string|nullable',
            'codzge'      => 'required_without_all:hotel_codes,k|string|nullable',
            'hotel_codes' => 'required_without_all:codzge,k|string|nullable',

            // si no viene rk, exigimos rooms en query (poco común, pero soportado)
            'rooms'          => 'required_without:rk|array|min:1',
            'rooms.*.adl'    => 'required_without:rk|integer|min:1|max:4',
            'rooms.*.chd'    => 'nullable|integer|min:0|max:3',
            'rooms.*.ages'   => 'nullable|array',
            'rooms.*.ages.*' => 'nullable|integer|min:0|max:17',

            'page'        => 'sometimes|integer|min:1',
            'mode'        => 'sometimes|in:fast,full', // ignorado
            'timeout'     => 'nullable|integer|min:1000|max:60000',
            'numrst'      => 'nullable|integer|min:1|',
            'batch_size'  => 'nullable|integer|min:1|max:200',
            'per_page'    => 'sometimes|integer|min:1|max:500',
            'endpoint'    => 'sometimes|url|nullable',
            'codsys'      => 'sometimes|string|nullable',
            'codage'      => 'sometimes|string|nullable',
            'user'        => 'sometimes|string|nullable',
            'pass'        => 'sometimes|string|nullable',
            'codtou'      => 'sometimes|string|nullable',
            'codnac'      => 'nullable|string|min:2|max:3',
        ]);
        $rooms = [];
        if ($rk = $request->query('rk')) {
            $rooms = (array) cache()->get($rk, []);
        } elseif (!empty($validated['rooms'])) {
            $rooms = (array) $validated['rooms'];
        }
        $rooms = $this->normalizeRooms($rooms); // lanza ValidationException si algo no cuadra
        // Config elegida (request -> config)
        $cfg = $this->getProviderConfig($request);
        if (empty($cfg['endpoint'])) {
            return response()->json(['error' => 'Endpoint no configurado (provider_cfg.endpoint está vacío)'], 500);
        }
        session(['provider_cfg' => $cfg]);

        $maxConcurrency = 8;          // antes dependía de $complete
        $connectTimeout = 1.0;
        $requestTimeout = 8.0;

        $timeoutMs  = $request->filled('timeout') ? (int)$request->input('timeout') : 10000;
        $numrst     = $request->filled('numrst')  ? (int)$request->input('numrst')  : null;
        $batchSize  = $request->filled('batch_size') ? (int)$request->input('batch_size') : 50;

        // UI per_page
        $perPage = (int) $request->input('per_page', $request->input('numrst', 20));
        $perPage = max(1, min($perPage, 500));

        // codnac efectivo (request -> config('itravex.codnac') -> '')
        $codnacReq = strtoupper(trim((string) $request->input('codnac', '')));
        $codnacCfg = strtoupper(trim((string) config('itravex.codnac', '')));
        $codnac    = $codnacReq !== '' ? $codnacReq : ($codnacCfg !== '' ? $codnacCfg : '');
        if ($codnac !== '' && strlen($codnac) < 2) {
            return response()->json(['error' => 'El código de nacionalidad debe tener al menos 2 caracteres'], 400);
        }

        // Fuente de hoteles: 'db' (por defecto) o 'provider'
        $source = $request->query('source', 'db');

        // Abrir sesión
        $sessionId = $this->openSession($cfg);
        if (!$sessionId) {
            return response()->json(['error' => 'No se pudo abrir sesión con el proveedor'], 500);
        }
        session(['ideses' => $sessionId, 'ideses_created_at' => now()]);

        $fecini = date('d/m/Y', strtotime($validated['fecini']));
        $fecfin = date('d/m/Y', strtotime($validated['fecfin']));

        // Métricas
        $poolSize    = 300;
        $currentPage = max(1, (int)$request->input('page', 1));
        $perf = [
            'db_ms'          => 0,
            'http_ms'        => 0,
            'parse_ms'       => 0,
            'aggregate_ms'   => 0,
            'total_ms'       => 0,
            'peak_mem_mb'    => 0.0,
            'hotels_page'    => 0,
            'rooms_page'     => 0,
            'pool_size'      => $poolSize,
            'pool_offset'    => 0,
            'timeout_ms_xml' => $timeoutMs,
            'numrst'         => $numrst ?? '—',
            'batch_size'     => $batchSize,
            'batches_sent'   => 0,
            'ui_per_page'    => $perPage,
        ];
        $t_app0 = microtime(true);

        // Parseo lista manual (desde k o desde hotel_codes)
        $manualCodes = [];
        $k = $request->query('k');
        if ($k) {
            $manualCodes = (array) (cache()->get($k, []));
        } elseif (!empty($validated['hotel_codes'])) {
            $raw = preg_split('/[,\s]+/u', trim($validated['hotel_codes']));
            $manualCodes = array_values(array_filter(array_unique(array_map('trim', $raw)), fn($v) => $v !== ''));
        }

        // ¿Usar modo ZONA nativo del proveedor? (solo si source=provider y NO hay lista manual)
        $usingZoneMode = empty($manualCodes) && !empty($validated['codzge']) && $source === 'provider';

        // Siempre modo completo (sin atajos/recorte)
        $maxConcurrency = 8;
        $connectTimeout = 1.0;
        $requestTimeout = 8.0;
        $earlyStop      = false;

        if ($timeoutMs) {
            // Respeta un timeout de request en función del timout del proveedor
            $requestTimeout = max($requestTimeout, min(60.0, ($timeoutMs / 1000.0) + 2.0));
        }

        $endpoint = $cfg['endpoint'];
        $internalCodtous = array_map('strtoupper', (array)($cfg['internal_codtous'] ?? ['LIB']));

        // Métricas/red comunes
        $reqMetrics   = [];
        $firstHeaders = null;
        $totalBytes   = 0;

        //Metricas respuesta xml
        $allResponses = [];
        $responsePreview = null;


        // Contenedores
        $allHotels            = [];
        $totalRooms           = 0;
        $internalRateCount    = 0;
        $externalRateCount    = 0;
        $externalALWRates     = 0;
        $providerRateCounts   = [];
        $hotelRateCounts      = [];
        $providerHotelSets    = [];

        // Contenedores para payloads
        $allPayloads    = [];
        $payloadMeta    = [];
        $payloadPreview = null;
        $firstPayload   = null;
        $zoneTotalHotels = 0;

        // Helper retry (modo zona)
        $doPostWithRetry = function (\GuzzleHttp\Client $client, string $endpoint, string $body, array &$reqMetrics, int $maxRetries = 2) {
            $attempt = 0;
            $delayMs = 300;
            while (true) {
                try {
                    return $client->post($endpoint, [
                        'body'        => $body,
                        'http_errors' => false,
                        'on_stats'    => function (\GuzzleHttp\TransferStats $stats) use (&$reqMetrics) {
                            $hs = $stats->getHandlerStats() ?? [];
                            $total      = (float)($hs['total_time'] ?? 0);
                            $namelookup = (float)($hs['namelookup_time'] ?? 0);
                            $connect    = (float)($hs['connect_time'] ?? 0);
                            $ssl        = (float)($hs['appconnect_time'] ?? 0);
                            $ttfb       = (float)($hs['starttransfer_time'] ?? 0);
                            $reqMetrics[] = [
                                'total_ms'      => (int) round($total * 1000),
                                'namelookup_ms' => (int) round($namelookup * 1000),
                                'connect_ms'    => (int) round($connect * 1000),
                                'ssl_ms'        => (int) round($ssl * 1000),
                                'ttfb_ms'       => (int) round($ttfb * 1000),
                                'download_ms'   => (int) round(($total - $ttfb) * 1000),
                                'primary_ip'    => $hs['primary_ip'] ?? null,
                                'http_code'     => $hs['http_code'] ?? null,
                            ];
                        },
                    ]);
                } catch (\GuzzleHttp\Exception\ConnectException | \GuzzleHttp\Exception\TransferException $e) {
                    if ($attempt >= $maxRetries) throw $e;
                    usleep($delayMs * 1000);
                    $delayMs = min(1500, (int)($delayMs * 2));
                    $attempt++;
                }
            }
        };

        // ---------- MODO ZONA (solo si source=provider) ----------
        if ($usingZoneMode) {
            $t_db0 = microtime(true);
            $zoneTotalHotels = \App\Models\Hotel::where('zone_code', $validated['codzge'])->count();
            $perf['db_ms'] += (int) round((microtime(true) - $t_db0) * 1000);

            // 🔁 CAMBIO: ahora inyectamos todos los <distri> construidos desde $rooms
            $buildXmlZone = function (int $indpag) use ($sessionId, $cfg, $fecini, $fecfin, $validated, $timeoutMs, $numrst, $codnac, $rooms) {
                $traduc    = "  <traduc>codsmo#</traduc>\n  <traduc>codcha#</traduc>\n  <traduc>codral#</traduc>";
                $numrstXml = $numrst ? "  <numrst>{$numrst}</numrst>\n" : "  <numrst>200</numrst>\n";
                $codnacXml = $codnac ? "  <codnac>{$codnac}</codnac>\n" : "";
                $codzge    = e($validated['codzge']);
                $distriXml = $this->buildDistriXml($rooms);

                return <<<XML
<DisponibilidadHotelPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
  <fecini>{$fecini}</fecini>
  <fecfin>{$fecfin}</fecfin>
  <codzge>{$codzge}</codzge>
{$distriXml}
{$traduc}
{$codnacXml}  <timout>{$timeoutMs}</timout>
{$numrstXml}  <indpag>{$indpag}</indpag>
  <chkscm>S</chkscm>
</DisponibilidadHotelPeticion>
XML;
            };


            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection'      => 'keep-alive',
                    'Content-Type'    => 'application/xml',
                ],
                'curl'            => [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0],
                'timeout'         => $requestTimeout,
                'connect_timeout' => $connectTimeout,
            ]);

            $indpag = 0;
            $receivedHotels = 0;
            $t_http0 = microtime(true);
            $note = null;

            do {
                $xmlBody = $buildXmlZone($indpag);
                $allPayloads[] = $xmlBody;

                try {
                    $resp = $doPostWithRetry($client, $endpoint, $xmlBody, $reqMetrics, 2);
                } catch (\GuzzleHttp\Exception\ConnectException | \GuzzleHttp\Exception\TransferException $e) {
                    \Log::warning('itravex_zone_timeout_or_transfer', ['indpag' => $indpag, 'msg' => $e->getMessage(), 'timeout_s' => $requestTimeout]);
                    if (!empty($allHotels)) {
                        $note = 'Timeout del proveedor: resultados parciales.';
                        break;
                    }
                    throw $e;
                }

                if ($firstHeaders === null) {
                    $firstHeaders = [
                        'date'              => $resp->getHeaderLine('Date') ?: null,
                        'server'            => $resp->getHeaderLine('Server') ?: '—',
                        'content_type'      => $resp->getHeaderLine('Content-Type') ?: '—',
                        'content_encoding'  => $resp->getHeaderLine('Content-Encoding') ?: '—',
                        'transfer_encoding' => $resp->getHeaderLine('Transfer-Encoding') ?: '—',
                        'connection'        => $resp->getHeaderLine('Connection') ?: '—',
                    ];
                }

                $rawBody = (string) $resp->getBody();
                $allResponses[] = $rawBody;
                $totalBytes += strlen($rawBody);
                $xml = @simplexml_load_string($rawBody, "SimpleXMLElement", LIBXML_NOCDATA);
                if ($xml === false) break;

                $returned = 0;
                foreach ($xml->infhot as $hotel) {
                    $returned++;
                    $hotelId   = (string) $hotel['id'];
                    $codser    = (string) $hotel->codser;
                    $hotelName = (string) $hotel->nomser;

                    $hotelInfo = [
                        'name'              => $hotelName,
                        'category'          => (string) $hotel->codsca,
                        'code'              => $codser,
                        'currency'          => (string) $hotel->coddiv,
                        'price'             => (float) $hotel->impbas,
                        'zone'              => (string) $hotel->codzge,
                        'hotel_internal_id' => $hotelId,
                    ];

                    $rooms = [];
                    foreach ($hotel->infhab as $room) {
                        $codtou     = strtoupper((string) $room->codtou);
                        $isInternal = in_array($codtou, $internalCodtous, true);
                        $refdisAttr = isset($room['refdis']) ? (int)$room['refdis'] : null;
                        $infrclVal  = (string) ($room->infrcl ?? '');

                        $providerRateCounts[$codtou] = ($providerRateCounts[$codtou] ?? 0) + 1;

                        // Si no viene refdis como atributo, intenta sacarlo del infrcl "1!~..." / "2!~..."
                        if ($refdisAttr === null && $infrclVal !== '') {
                            $head = strtok($infrclVal, '!~');
                            if (is_numeric($head)) $refdisAttr = (int)$head;
                        }

                        if ($codtou !== '') {
                            if (!isset($providerHotelSets[$codtou])) $providerHotelSets[$codtou] = [];
                            $providerHotelSets[$codtou][$codser] = true;
                        }

                        if ($isInternal) {
                            $internalRateCount++;
                        } else {
                            $externalRateCount++;
                            if ($codtou === 'ALW') $externalALWRates++;
                        }

                        $price = (float) $room->impnoc;
                        if ($price == 0 && isset($room->impcom)) $price = (float) $room->impcom;

                        $rooms[] = [
                            'room_type'        => (string) $room->codsmo,
                            'room_code'        => (string) $room->codcha,
                            'board'            => (string) $room->codral,
                            'price_per_night'  => $price,
                            'availability'     => (string) $room->cupinv,
                            'room_internal_id' => (string) $room['id'],
                            'is_internal'      => $isInternal,
                            'codtou'           => $codtou,
                            'refdis'          => $refdisAttr,
                            'infrcl'          => $infrclVal,

                        ];
                        $totalRooms++;
                    }

                    $hotelInfo['rooms'] = $rooms;
                    $allHotels[] = $hotelInfo;
                    $receivedHotels++;
                }

                $expected = (int)($numrst ?: 200);
                if ($returned < $expected) break;
                $indpag++;
            } while (true);

            $perf['http_ms'] = (int) round((microtime(true) - $t_http0) * 1000);
            if (!empty($note)) {
                $firstHeaders = $firstHeaders ?? [];
                $firstHeaders['note'] = $note;
            }

            $payloadMeta = [
                'mode'          => 'zone',
                'batches'       => count($allPayloads),
                'showing'       => 0,
                'codser_total'  => null,
                'zone'          => $validated['codzge'] ?? null,
            ];
        }
        // ---------- MODO POR LISTA/CODSER (por BD) ----------
        else {
            $t_db0 = microtime(true);

            if (!empty($manualCodes)) {
                if (!empty($validated['codzge'])) {
                    $manualCodes = \App\Models\Hotel::where('zone_code', $validated['codzge'])
                        ->whereIn('codser', $manualCodes)
                        ->pluck('codser')->all();
                }
                $codesPool = array_values(array_filter(array_unique($manualCodes)));
            } else {
                $codesPool = \App\Models\Hotel::where('zone_code', $validated['codzge'])
                    ->orderBy('name')
                    ->pluck('codser')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            $zoneTotalHotels = count($codesPool);
            $perf['db_ms'] += (int) round((microtime(true) - $t_db0) * 1000);

            if (empty($codesPool)) {
                $empty = new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage, $currentPage, ['path' => url()->current()]);
                return view('availability.results', [
                    'hotels'                     => $empty,
                    'totalRooms'                 => 0,
                    'internalRateCount'          => 0,
                    'externalRateCount'          => 0,
                    'externalALWRates'           => 0,
                    'providerRateCounts'         => [],
                    'hotelRateCounts'            => [],
                    'hotelRateCountsPage'        => [],
                    'providerHotelCounts'        => [],
                    'providerHotelCountsPage'    => [],
                    'httpMeta' => [
                        'status'            => 200,
                        'elapsed_ms'        => 0,
                        'size_decompressed' => 0,
                        'content_length'    => '—',
                        'content_encoding'  => '—',
                        'transfer_encoding' => '—',
                        'connection'        => '—',
                        'content_type'      => '—',
                        'date'              => null,
                        'total_ms_pool'     => '—',
                        'total_ms'          => '—',
                        'ttfb_ms'           => '—',
                        'download_ms'       => '—',
                        'connect_ms'        => '—',
                        'ssl_ms'            => '—',
                        'namelookup_ms'     => '—',
                        'primary_ip'        => '—',
                    ],
                    'perf' => array_merge($perf, [
                        'http_ms'      => 0,
                        'parse_ms'     => 0,
                        'aggregate_ms' => 0,
                        'total_ms'     => (int) round((microtime(true) - $t_app0) * 1000),
                        'peak_mem_mb'  => round(memory_get_peak_usage(true) / 1048576, 1),
                        'hotels_page'  => 0,
                        'rooms_page'   => 0,
                    ]),
                    'selectionNote'      => !empty($manualCodes)
                        ? "{$perPage} visibles — consultados en lotes de 100 (códigos manuales)"
                        : "{$perPage} visibles — consultados en lotes de 100 (modo codser por BD)",
                    'zoneTotalHotels'    => $zoneTotalHotels,
                    'effective' => [
                        'codnac'       => $codnac,
                        'timeout_ms'   => $timeoutMs,
                        'per_page'     => $perPage,
                        'from_zone'    => $validated['codzge'] ?? null,
                        'manual_codes' => !empty($manualCodes),
                        'zone_total'   => $zoneTotalHotels,
                        'source'       => $source,
                        // 🔴 NUEVO: pasar la ocupación normalizada a la vista/API
                        'rooms'        => $rooms,
                    ],
                    'providers'           => [],
                    'hotelProviderMatrix' => [],
                    'firstPayload'  => null,
                    'payloads'      => [],
                    'payload_meta'  => ['mode' => 'codes', 'batches' => 0, 'showing' => 0, 'codser_total' => 0],
                ]);
            }

            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection'      => 'keep-alive',
                    'Content-Type'    => 'application/xml',
                ],
                'curl' => [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0],
            ]);

            // 🔁 CAMBIO: inyectamos distri multihab también en modo por códigos
            $buildXmlMulti = function (array $codsers) use ($sessionId, $cfg, $fecini, $fecfin, $timeoutMs, $numrst, $codnac, $rooms) {
                $codserLines = implode("\n", array_map(fn($c) => "  <codser>{$c}</codser>", $codsers));
                $traduc      = "  <traduc>codsmo#</traduc>\n  <traduc>codcha#</traduc>\n  <traduc>codral#</traduc>";
                $numrstXml   = $numrst ? "  <numrst>{$numrst}</numrst>\n" : "";
                $codnacXml   = $codnac ? "  <codnac>{$codnac}</codnac>\n" : "";
                $distriXml   = $this->buildDistriXml($rooms);

                return <<<XML
<DisponibilidadHotelPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
  <fecini>{$fecini}</fecini>
  <fecfin>{$fecfin}</fecfin>
{$codserLines}
{$distriXml}
{$traduc}
{$codnacXml}  <timout>{$timeoutMs}</timout>
{$numrstXml}  <indpag>0</indpag>
  <chkscm>S</chkscm>
</DisponibilidadHotelPeticion>
XML;
            };



            $batchSizeCodser = 50;
            $chunks = array_chunk($codesPool, $batchSizeCodser, false);
            $perf['batches_sent'] = count($chunks);

            foreach ($chunks as $i => $chunk) {
                $allPayloads[] = $buildXmlMulti($chunk);
            }

            $promises = [];
            foreach ($allPayloads as $i => $payloadXml) {
                $promises["batch_{$i}"] = $client->postAsync($endpoint, [
                    'body'            => $payloadXml,
                    'connect_timeout' => $connectTimeout,
                    'timeout'         => $requestTimeout,
                    'on_stats'        => function (\GuzzleHttp\TransferStats $stats) use (&$reqMetrics) {
                        $hs = $stats->getHandlerStats() ?? [];
                        $total      = (float)($hs['total_time'] ?? 0);
                        $namelookup = (float)($hs['namelookup_time'] ?? 0);
                        $connect    = (float)($hs['connect_time'] ?? 0);
                        $ssl        = (float)($hs['appconnect_time'] ?? 0);
                        $ttfb       = (float)($hs['starttransfer_time'] ?? 0);
                        $reqMetrics[] = [
                            'total_ms'      => (int) round($total * 1000),
                            'namelookup_ms' => (int) round($namelookup * 1000),
                            'connect_ms'    => (int) round($connect * 1000),
                            'ssl_ms'        => (int) round($ssl * 1000),
                            'ttfb_ms'       => (int) round($ttfb * 1000),
                            'download_ms'   => (int) round(($total - $ttfb) * 1000),
                            'primary_ip'    => $hs['primary_ip'] ?? null,
                            'http_code'     => $hs['http_code'] ?? null,
                        ];
                    },
                ]);
            }

            $t_http0 = microtime(true);
            $each = new \GuzzleHttp\Promise\EachPromise($promises, [
                'concurrency' => $maxConcurrency,
                'fulfilled' => function ($response) use (&$firstHeaders, &$totalBytes, &$allHotels, &$totalRooms, &$internalRateCount, &$externalRateCount, &$externalALWRates, &$providerRateCounts, &$hotelRateCounts, &$providerHotelSets, &$allResponses) {
                    if ($firstHeaders === null) {
                        $firstHeaders = [
                            'date'              => $response->getHeaderLine('Date') ?: null,
                            'server'            => $response->getHeaderLine('Server') ?: '—',
                            'content_type'      => $response->getHeaderLine('Content-Type') ?: '—',
                            'content_encoding'  => $response->getHeaderLine('Content-Encoding') ?: '—',
                            'transfer_encoding' => $response->getHeaderLine('Transfer-Encoding') ?: '—',
                            'connection'        => $response->getHeaderLine('Connection') ?: '—',
                        ];
                    }
                    $rawBody = (string) $response->getBody();
                    $allResponses[] = $rawBody;
                    $totalBytes += strlen($rawBody);

                    $xml = @simplexml_load_string($rawBody, "SimpleXMLElement", LIBXML_NOCDATA);
                    if ($xml === false) return;

                    foreach ($xml->infhot as $hotel) {
                        $hotelId   = (string) $hotel['id'];
                        $codser    = (string) $hotel->codser;
                        $hotelName = (string) $hotel->nomser;

                        $hotelInfo = [
                            'name'              => $hotelName,
                            'category'          => (string) $hotel->codsca,
                            'code'              => $codser,
                            'currency'          => (string) $hotel->coddiv,
                            'price'             => (float) $hotel->impbas,
                            'zone'              => (string) $hotel->codzge,
                            'hotel_internal_id' => $hotelId,
                        ];

                        $rooms = [];
                        foreach ($hotel->infhab as $room) {
                            $codtou     = strtoupper((string) $room->codtou);
                            $isInternal = ($codtou === 'LIB');

                            $providerRateCounts[$codtou] = ($providerRateCounts[$codtou] ?? 0) + 1;
                            if ($codtou !== '') {
                                if (!isset($providerHotelSets[$codtou])) $providerHotelSets[$codtou] = [];
                                $providerHotelSets[$codtou][$codser] = true;
                            }

                            if (!isset($hotelRateCounts[$codser])) {
                                $hotelRateCounts[$codser] = ['name' => $hotelName ?: $codser, 'count' => 0];
                            }
                            $hotelRateCounts[$codser]['count']++;

                            if ($isInternal) $internalRateCount++;
                            else {
                                $externalRateCount++;
                                if ($codtou === 'ALW') $externalALWRates++;
                            }

                            $price = (float) $room->impnoc;
                            if ($price == 0 && isset($room->impcom)) $price = (float) $room->impcom;

                            // 🔴 Añadido: leer infrcl y refdis (atributo o inferido)
                            $refdisAttr = isset($room['refdis']) ? (int) $room['refdis'] : null;
                            $infrclVal  = (string) ($room->infrcl ?? '');
                            if ($refdisAttr === null && $infrclVal !== '') {
                                $head = strtok($infrclVal, '!~');
                                if (is_numeric($head)) $refdisAttr = (int) $head;
                            }

                            $rooms[] = [
                                'room_type'        => (string) $room->codsmo,
                                'room_code'        => (string) $room->codcha,
                                'board'            => (string) $room->codral,
                                'price_per_night'  => $price,
                                'availability'     => (string) ($room->cupest ?? ''),
                                'room_internal_id' => (string) $room['id'],
                                'is_internal'      => $isInternal,
                                'codtou'           => $codtou,

                                // ✅ Claves que el Blade usa para packs 2x
                                'refdis'           => $refdisAttr,
                                'infrcl'           => $infrclVal,
                            ];
                            $totalRooms++;
                        }

                        $hotelInfo['rooms'] = $rooms;
                        $allHotels[] = $hotelInfo;
                    }
                },

                'rejected' => function ($reason, $batchKey) {
                    \Log::warning('itravex_batch_failed', ['batch' => $batchKey, 'reason' => (string)$reason]);
                },
            ]);

            $each->promise()->wait();
            $perf['http_ms'] = (int) round((microtime(true) - $t_http0) * 1000);

            $payloadMeta = [
                'mode'          => 'codes',
                'batches'       => count($allPayloads),
                'showing'       => 0,
                'codser_total'  => count($codesPool),
                'zone'          => $validated['codzge'] ?? null,
            ];
        }

        // ---------- TAIL COMÚN ----------
        if (!empty($allHotels)) {
            usort($allHotels, fn($a, $b) => ($a['price'] <=> $b['price']) ?: strcmp($a['name'], $b['name']));
        }

        if (!isset($offsetWithinBlock)) $offsetWithinBlock = 0;

        $availableTotal = count($allHotels);
        $lastPage = max(1, (int) ceil($availableTotal / $perPage));
        if ($currentPage > $lastPage) {
            $currentPage = $lastPage;
        }

        $offset = ($currentPage - 1) * $perPage;
        $visibleHotels = array_slice($allHotels, $offset, $perPage);

        // Enriquecer cada hotel visible con totales y desglose por proveedor
        foreach ($visibleHotels as &$h) {
            $h['rate_count'] = is_countable($h['rooms'] ?? null) ? count($h['rooms']) : 0;
            $provCounts = [];
            foreach ($h['rooms'] ?? [] as $r) {
                $p = (string)($r['codtou'] ?? '');
                if ($p === '') continue;
                $provCounts[$p] = ($provCounts[$p] ?? 0) + 1;
            }
            arsort($provCounts);
            $h['provider_counts'] = $provCounts;
        }
        unset($h);

        $hotelRateCountsPage = [];
        foreach ($visibleHotels as $h) {
            $code = $h['code'];
            $hotelRateCountsPage[$code] = [
                'name'  => $h['name'] ?: $code,
                'count' => count($h['rooms']),
            ];
        }
        uasort($hotelRateCountsPage, fn($a, $b) => $b['count'] <=> $a['count']);

        if (!empty($hotelRateCounts)) {
            uasort($hotelRateCounts, fn($a, $b) => $b['count'] <=> $a['count']);
        }

        $providerHotelCounts = [];
        foreach ($providerHotelSets as $prov => $set) $providerHotelCounts[$prov] = count($set);
        arsort($providerHotelCounts);

        $providerHotelSetsPage = [];
        foreach ($visibleHotels as $h) {
            foreach ($h['rooms'] as $room) {
                $p = (string)($room['codtou'] ?? '');
                if ($p === '') continue;
                if (!isset($providerHotelSetsPage[$p])) $providerHotelSetsPage[$p] = [];
                $providerHotelSetsPage[$p][$h['code']] = true;
            }
        }
        $providerHotelCountsPage = [];
        foreach ($providerHotelSetsPage as $prov => $set) $providerHotelCountsPage[$prov] = count($set);
        arsort($providerHotelCountsPage);

        $paginated = new \Illuminate\Pagination\LengthAwarePaginator(
            collect($visibleHotels)->values(),
            $availableTotal,
            $perPage,
            $currentPage,
            ['path' => url()->current()]
        );

        $avg = function (array $arr, string $k) {
            if (empty($arr)) return null;
            $vals = array_column($arr, $k);
            $vals = array_filter($vals, fn($v) => $v !== null);
            return count($vals) ? (int) round(array_sum($vals) / count($vals)) : null;
        };
        $firstNonNull = function (array $arr, string $k) {
            foreach ($arr as $row) if (!empty($row[$k])) return $row[$k];
            return '—';
        };

        $perf['hotels_page'] = count($visibleHotels);
        $perf['rooms_page']  = $totalRooms;
        $perf['peak_mem_mb'] = round(memory_get_peak_usage(true) / 1048576, 1);
        $perf['total_ms']    = (int) round((microtime(true) - $t_app0) * 1000);

        $httpMeta = [
            'status'            => 200,
            'date'              => $firstHeaders['date']              ?? null,
            'server'            => $firstHeaders['server']            ?? '—',
            'content_type'      => $firstHeaders['content_type']      ?? '—',
            'content_encoding'  => $firstHeaders['content_encoding']  ?? '—',
            'transfer_encoding' => $firstHeaders['transfer_encoding'] ?? '—',
            'connection'        => $firstHeaders['connection']        ?? '—',
            'elapsed_ms'        => $perf['total_ms'],
            'size_decompressed' => $totalBytes,
            'content_length'    => '—',
            'total_ms_pool'     => $avg($reqMetrics, 'total_ms')      ?? '—',
            'total_ms'          => $avg($reqMetrics, 'total_ms')      ?? '—',
            'ttfb_ms'           => $avg($reqMetrics, 'ttfb_ms')       ?? '—',
            'download_ms'       => $avg($reqMetrics, 'download_ms')   ?? '—',
            'connect_ms'        => $avg($reqMetrics, 'connect_ms')    ?? '—',
            'ssl_ms'            => $avg($reqMetrics, 'ssl_ms')        ?? '—',
            'namelookup_ms'     => $avg($reqMetrics, 'namelookup_ms') ?? '—',
            'primary_ip'        => $firstNonNull($reqMetrics, 'primary_ip'),
            'note'              => $firstHeaders['note'] ?? null,
        ];

        // Preview de payloads (hasta 5)
        if (!empty($allPayloads)) {
            $maxShow = 5;
            $toShow = array_slice($allPayloads, 0, $maxShow);
            $parts = [];
            $total = count($allPayloads);
            foreach ($toShow as $i => $px) {
                $idx = $i + 1;
                $parts[] = "<!-- ===== BATCH {$idx}/{$total} ===== -->\n" . $px;
            }
            $payloadPreview = implode("\n\n", $parts);
            $payloadMeta['showing'] = count($toShow);
        } else {
            $payloadPreview = null;
            if (empty($payloadMeta)) {
                $payloadMeta = ['mode' => $usingZoneMode ? 'zone' : 'codes', 'batches' => 0, 'showing' => 0, 'codser_total' => null, 'zone' => $validated['codzge'] ?? null];
            }
        }
        $firstPayload = $payloadPreview;
        $responseMeta = ['batches' => count($allResponses), 'showing' => 0];
        if (!empty($allResponses)) {
            $maxShow = 5;
            $toShow = array_slice($allResponses, 0, $maxShow);
            $parts = [];
            $total = count($allResponses);
            foreach ($toShow as $i => $rx) {
                $idx = $i + 1;
                $parts[] = "<!-- ===== RESPONSE {$idx}/{$total} ===== -->\n" . $rx;
            }
            $responsePreview = implode("\n\n", $parts);
            $responseMeta['showing'] = count($toShow);
        }

        // Export CSV
        if ($request->query('export') === 'csv') {
            $filename = 'disponibilidad_' . date('Ymd_His') . '.csv';
            $providerTotals = [];
            foreach ($visibleHotels as $h) {
                foreach ($h['rooms'] as $room) {
                    $p = (string)($room['codtou'] ?? '');
                    if ($p === '') continue;
                    $providerTotals[$p] = ($providerTotals[$p] ?? 0) + 1;
                }
            }
            uksort($providerTotals, function ($a, $b) use ($providerTotals) {
                $cmp = ($providerTotals[$b] <=> $providerTotals[$a]);
                return $cmp !== 0 ? $cmp : strcmp($a, $b);
            });
            $providers = array_keys($providerTotals);

            $matrix = [];
            foreach ($visibleHotels as $h) {
                $counts = array_fill_keys($providers, 0);
                foreach ($h['rooms'] as $room) {
                    $p = (string)($room['codtou'] ?? '');
                    if ($p !== '' && isset($counts[$p])) $counts[$p]++;
                }
                $matrix[] = [
                    'name'   => $h['name'] . " ({$h['code']})",
                    'counts' => $counts,
                    'total'  => array_sum($counts),
                ];
            }
            usort($matrix, fn($a, $b) => $b['total'] <=> $a['total']);

            return response()->streamDownload(function () use ($matrix, $providers) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");
                fputcsv($out, array_merge(['Nombre Hotel'], $providers, ['Total']));
                foreach ($matrix as $row) {
                    fputcsv($out, array_merge([$row['name']], array_values($row['counts']), [$row['total']]));
                }
                fclose($out);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // JSON
        if ($request->wantsJson()) {
            return response()->json([
                'data'                       => $paginated->items(),
                'current_page'               => $paginated->currentPage(),
                'last_page'                  => $paginated->lastPage(),
                'total'                      => $paginated->total(),
                'zone_total_hotels'          => $zoneTotalHotels,
                'total_rooms'                => $totalRooms,
                'internal_rates'             => $internalRateCount,
                'external_rates'             => $externalRateCount,
                'external_alw_rates'         => $externalALWRates,
                'provider_rate_counts'       => $providerRateCounts,
                'hotel_rate_counts'          => $hotelRateCounts,
                'hotel_rate_counts_page'     => $hotelRateCountsPage,
                'provider_hotel_counts'      => $providerHotelCounts,
                'provider_hotel_counts_page' => $providerHotelCountsPage,
                'http_meta'                  => $httpMeta,
                'perf'                       => $perf,
                'selection_note'             => !empty($manualCodes)
                    ? "{$perPage} visibles — consultados en lotes de 100 (códigos manuales)"
                    : ($usingZoneMode
                        ? "{$perPage} visibles — paginación nativa por zona (indpag/numrst)"
                        : "{$perPage} visibles — consultados en lotes de 100 (modo codser por BD)"),
                'effective' => [
                    'codnac'       => $codnac,
                    'timeout_ms'   => $timeoutMs,
                    'per_page'     => $perPage,
                    'from_zone'    => $validated['codzge'] ?? null,
                    'manual_codes' => !empty($manualCodes),
                    'zone_total'   => $zoneTotalHotels,
                    'source'       => $source,
                ],
                'providers'             => array_keys($providerRateCounts ?? []),
                'hotel_provider_matrix' => [],
                'payloads'              => $allPayloads,
                'payload_meta'          => $payloadMeta,
                'payload_preview'       => $payloadPreview,
                'firstResponse'    => $responsePreview, 
            ]);
        }

        // Vista
        return view('availability.results', [
            'hotels'                   => $paginated,
            'totalRooms'               => $totalRooms,
            'internalRateCount'        => $internalRateCount,
            'externalRateCount'        => $externalRateCount,
            'externalALWRates'         => $externalALWRates,
            'providerRateCounts'       => $providerRateCounts,
            'hotelRateCounts'          => $hotelRateCounts,
            'hotelRateCountsPage'      => $hotelRateCountsPage,
            'providerHotelCounts'      => $providerHotelCounts,
            'providerHotelCountsPage'  => $providerHotelCountsPage,
            'httpMeta'                 => $httpMeta,
            'perf'                     => $perf,
            'rooms'        => $rooms,
            'selectionNote'            => !empty($manualCodes)
                ? "{$perPage} visibles — consultados en lotes de 100 (códigos manuales)"
                : ($usingZoneMode
                    ? "{$perPage} visibles — paginación nativa por zona (indpag/numrst)"
                    : "{$perPage} visibles — consultados en lotes de 100 (modo codser por BD)"),
            'zoneTotalHotels'          => $zoneTotalHotels,
            'effective' => [
                'codnac'       => $codnac,
                'timeout_ms'   => $timeoutMs,
                'per_page'     => $perPage,
                'from_zone'    => $validated['codzge'] ?? null,
                'manual_codes' => !empty($manualCodes),
                'zone_total'   => $zoneTotalHotels,
                'source'       => $source,
            ],
            'providers'            => array_keys($providerRateCounts ?? []),
            'hotelProviderMatrix'  => [],
            'firstPayload'         => $payloadPreview,
            'payloads'             => $allPayloads,
            'payload_meta'         => $payloadMeta,
            'firstResponse'    => $responsePreview, 
        ]);
    }












    public function showZoneForm()
    {
        $zones = Zone::orderBy('name')->get();
        return view('availability.select-zone', compact('zones'));
    }

    private function openSession(array $cfg): ?string
    {
        if (empty($cfg['endpoint'])) {
            return null;
        }

        // No loguear este XML (contiene credenciales)
        $xml = <<<XML
<SesionAbrirPeticion>
    <codsys>{$cfg['codsys']}</codsys>
    <codage>{$cfg['codage']}</codage>
    <idtusu>{$cfg['user']}</idtusu>
    <pasusu>{$cfg['pass']}</pasusu>
</SesionAbrirPeticion>
XML;

        $response = Http::timeout(20)
            ->withHeaders(['Accept-Encoding' => 'gzip'])
            ->withBody($xml, 'application/xml')
            ->post($cfg['endpoint']);

        if (!$response->successful()) {
            return null;
        }

        $data = simplexml_load_string($response->body());
        return isset($data->ideses) ? (string) $data->ideses : null;
    }


    public function submitLock(Request $request)
    {
        Log::channel('itravex')->info("⚠️ Entrando a submitLock (soporte multi-habitación)");

        // 🔹 Validación (birthdates ahora es opcional; distri soportado)
        $data = $request->validate([
            'hotel_code'        => 'required|string',
            'codzge'            => 'nullable|string',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date',
            'hotel_name'        => 'required|string',
            'board'             => 'required|string',
            'currency'          => 'required|string',
            'hotel_internal_id' => 'required|string',

            // birthdates anidado por habitación/adults/children (OPCIONAL)
            'birthdates'                 => 'nullable|array',
            'birthdates.*'               => 'array',
            'birthdates.*.adults'        => 'nullable|array',
            'birthdates.*.adults.*'      => 'nullable|date',
            'birthdates.*.children'      => 'nullable|array',
            'birthdates.*.children.*'    => 'nullable|date',

            // Soporte multi-habitación
            'pack'                          => 'nullable|array|min:1',
            'pack.*.room_internal_id'       => 'required_with:pack|string',
            'pack.*.price_per_night'        => 'required_with:pack|numeric',
            'pack.*.refdis'                 => 'nullable|string',

            // Compat habitación única
            'room_internal_id'              => 'nullable|string',
            'price_per_night'               => 'nullable',

            // 🔹 Para cálculo automático si no vienen birthdates
            'distri'                        => 'nullable|array',
            'distri.*.numadl'               => 'nullable|integer|min:0',
            'distri.*.numnin'               => 'nullable|integer|min:0',
            'distri.*.edanin'               => 'nullable|array', // edades niños
            'distri.*.edanin.*'             => 'nullable|integer|min:0',
        ]);

        $entryDate = new \DateTime($data['start_date']); // referencia para calcular FECNAC
        $ADULT_DEFAULT_YEARS = (int) (config('itravex.adult_default_years', 30)); // configurable

        // ✅ birthdatesByRoom: siempre lo tendremos (llegue del form o lo derivamos)
        $birthdatesByRoom = [];

        if (!empty($data['birthdates'])) {
            // Normaliza a 0-based
            $birthdatesByRoom = array_values($data['birthdates']);
        } else {
            // ⚙️ Derivar de distri: por cada habitación
            $distri = $data['distri'] ?? [];
            if (empty($distri)) {
                return back()->withErrors(['No se recibieron ni fechas de nacimiento ni distribución para calcularlas.']);
            }

            // distri viene 1..N ⇒ normalizamos a 0..N-1
            $normalized = [];
            foreach ($distri as $i1 => $v) {
                $normalized[] = [
                    'numadl' => (int)($v['numadl'] ?? 0),
                    'numnin' => (int)($v['numnin'] ?? 0),
                    'edanin' => isset($v['edanin']) && is_array($v['edanin']) ? array_values($v['edanin']) : [],
                ];
            }

            // helper: fecha Y-m-d a partir de "edad" en años (aprox — restamos 6 meses para evitar límites)
            $dobFromAge = function (\DateTime $ref, int $years) {
                $dt = (clone $ref);
                $dt->modify("-{$years} years")->modify('-6 months');
                return $dt->format('Y-m-d');
            };

            foreach ($normalized as $zeroIdx => $room) {
                $numAdl = max(0, (int)$room['numadl']);
                $numNin = max(0, (int)$room['numnin']);
                $kidAges = array_values(array_filter($room['edanin'] ?? [], fn($x) => $x !== '' && $x !== null));

                // ⬇️ NUEVO: intentar leer edades de adultos desde rooms[zeroIdx][adult_ages][]
                $adultAgesFromRooms = (array) $request->input("rooms.$zeroIdx.adult_ages", []);

                $adults = [];
                for ($i = 0; $i < $numAdl; $i++) {
                    if (isset($adultAgesFromRooms[$i]) && $adultAgesFromRooms[$i] !== '') {
                        $age = (int) $adultAgesFromRooms[$i];
                    } else {
                        $age = $ADULT_DEFAULT_YEARS; // fallback configurable
                    }
                    $adults[] = $dobFromAge($entryDate, max(12, $age));
                }

                $children = [];
                for ($i = 0; $i < $numNin; $i++) {
                    $age = isset($kidAges[$i]) ? (int)$kidAges[$i] : 7;
                    $children[] = $dobFromAge($entryDate, max(0, $age));
                }

                $birthdatesByRoom[] = [
                    'adults'   => $adults,
                    'children' => $children,
                ];
            }
        }

        // 🔹 Aplanar fechas (adults + children) conservando el orden por habitación
        $flatBirthdates = [];
        foreach ($birthdatesByRoom as $roomIndex => $sets) {
            foreach (['adults', 'children'] as $group) {
                if (!empty($sets[$group]) && is_array($sets[$group])) {
                    foreach ($sets[$group] as $d) {
                        if ($d !== null && $d !== '') {
                            $flatBirthdates[] = $d; // YYYY-MM-DD (o lo que venga)
                        }
                    }
                }
            }
        }
        // Compat plano
        if (empty($flatBirthdates) && !empty($data['birthdates']) && is_array($data['birthdates'])) {
            foreach ($data['birthdates'] as $maybeDate) {
                if (!is_array($maybeDate) && $maybeDate !== '') {
                    $flatBirthdates[] = $maybeDate;
                }
            }
        }
        if (empty($flatBirthdates)) {
            return back()->withErrors(['No se pudieron construir fechas de nacimiento.']);
        }

        // 🔹 Helper fecha → dd/mm/YYYY
        $toDmy = function ($value) {
            if (is_array($value)) {
                foreach ($value as $v) {
                    if (!is_array($v)) {
                        $value = $v;
                        break;
                    }
                }
            }
            $value = trim((string)$value);
            if ($value === '') return null;

            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $dt = \DateTime::createFromFormat('Y-m-d', $value);
                return $dt ? $dt->format('d/m/Y') : null;
            }
            if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
                return $value; // ya dd/mm/YYYY
            }
            $ts = strtotime($value);
            return $ts ? date('d/m/Y', $ts) : null;
        };

        // 🔹 Construir <pasage> con <FECNAC>
        $pasageXml = '';
        $globalIndex = 1;
        foreach ($flatBirthdates as $rawDate) {
            $dmy = $toDmy($rawDate);
            if ($dmy === null) {
                Log::channel('itravex')->warning('Fecha de nacimiento inválida/no parseable', ['raw' => $rawDate]);
                return back()->withErrors(['Alguna fecha de nacimiento no es válida.']);
            }
            $pasageXml .= "<adl id=\"{$globalIndex}\"><FECNAC>{$dmy}</FECNAC></adl>";
            $globalIndex++;
        }

        // 🔹 Normalizar habitaciones (pack o individual)
        $rooms = [];
        if (!empty($data['pack'])) {
            foreach (array_values($data['pack']) as $idx => $r) {
                $rooms[$idx] = [
                    'room_internal_id' => (string)($r['room_internal_id'] ?? ''),
                    'price_per_night'  => $r['price_per_night'] ?? null,
                    'refdis'           => $r['refdis'] ?? null,
                ];
            }
        } elseif (!empty($data['room_internal_id'])) {
            $rooms[0] = [
                'room_internal_id' => (string)$data['room_internal_id'],
                'price_per_night'  => $data['price_per_night'] ?? null,
                'refdis'           => $data['refdis'] ?? null,
            ];
        } else {
            return back()->withErrors(['No se recibió ninguna habitación válida (ni pack ni individual).']);
        }

        // 🔹 Asignar PASAJEROS por habitación según birthdatesByRoom (0-based)
        $roomPasids = [];
        $cursor = 1;
        foreach ($birthdatesByRoom as $roomIndex => $sets) {
            $countAdults = !empty($sets['adults'])   ? count($sets['adults'])   : 0;
            $countKids   = !empty($sets['children']) ? count($sets['children']) : 0;
            $n = $countAdults + $countKids;

            $ids = [];
            for ($j = 0; $j < $n; $j++) {
                $ids[] = $cursor++;
            }
            $roomPasids[$roomIndex] = $ids;
        }

        // 🔹 Config proveedor
        $cfg = session('provider_cfg') ?? $this->getProviderConfig($request);
        if (empty($cfg['endpoint'])) {
            return back()->withErrors(['Endpoint no configurado (provider_cfg.endpoint está vacío).']);
        }

        // 🔹 Sesión con el proveedor
        $sessionId = session('ideses');
        if (!$sessionId) {
            $sessionId = $this->openSession($cfg);
            if (!$sessionId) {
                return back()->withErrors(['No se pudo abrir sesión']);
            }
            session(['ideses' => $sessionId, 'ideses_created_at' => now()]);
        }

        // 🔹 IDs padre
        $bloserId = (string) $data['hotel_internal_id'];

        // 🔹 Generar <dissmo> por habitación con sus pasid
        $bloserXml = '';
        foreach ($rooms as $idx => $r) {
            $dissmoId = $r['room_internal_id'];
            $ids = $roomPasids[$idx] ?? [];
            $pasidsXml = '';
            foreach ($ids as $pid) {
                $pasidsXml .= "<pasid>{$pid}</pasid>";
            }

            $bloserXml .= "<bloser id=\"{$bloserId}\"><dissmo id=\"{$dissmoId}\"><numuni>1</numuni>{$pasidsXml}</dissmo></bloser>";
        }

        // 🔹 XML final
        $xml = <<<XML
<BloqueoServicioPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
  <tipcon>V</tipcon>
  <pasage>{$pasageXml}</pasage>
  {$bloserXml}
  <accion>A</accion>
</BloqueoServicioPeticion>
XML;

        Log::channel('itravex')->debug("🔵 XML de petición BloqueoServicio:\n" . $xml);

        // 🔹 Envío
        $http = Http::withHeaders([
            'Accept-Encoding' => 'gzip',
            'Content-Type'    => 'application/xml',
        ])->timeout(30)->withBody($xml, 'application/xml');

        $response = $http->post($cfg['endpoint']);

        if (!$response->successful()) {
            Log::channel('itravex')->error("❌ HTTP fallo en BloqueoServicio", ['status' => $response->status(), 'body' => $response->body()]);
            return back()->withErrors(['Error de conexión con el proveedor']);
        }

        $raw = $response->body();
        Log::channel('itravex')->info("🟢 Respuesta BloqueoServicio RAW:\n" . $raw);

        $xmlResponse = @simplexml_load_string($raw);
        if ($xmlResponse === false) {
            Log::channel('itravex')->error("❌ No se pudo parsear XML de BloqueoServicio");
            return back()->withErrors(['Respuesta inválida del proveedor']);
        }

        $tiperr = trim((string)($xmlResponse->tiperr ?? ''));
        $txterr = trim((string)($xmlResponse->txterr ?? ''));
        $coderr = trim((string)($xmlResponse->coderr ?? ''));
        if ($txterr !== '' || $coderr !== '') {
            Log::channel('itravex')->error("❌ Bloqueo rechazado por el proveedor", [
                'coderr' => $coderr ?: '(vacío)',
                'tiperr' => $tiperr ?: '(vacío)',
                'txterr' => $txterr ?: '(vacío)',
            ]);
            return back()->withErrors(['Error del proveedor en el bloqueo: ' . ($txterr ?: $coderr)]);
        }

        // 🔹 Extraer locata
        $locata = null;
        try {
            $nodes = $xmlResponse->xpath('//locata');
            if (!empty($nodes) && isset($nodes[0])) {
                $locata = trim((string)$nodes[0]);
            }
        } catch (\Throwable $e) { /* no-op */
        }
        if (!$locata && isset($xmlResponse->resser->locata)) {
            $locata = (string)$xmlResponse->resser->locata;
        } elseif (!$locata && isset($xmlResponse->resser->estsmo->locata)) {
            $locata = (string)$xmlResponse->resser->estsmo->locata;
        } elseif (!$locata && isset($xmlResponse->locata)) {
            $locata = (string)$xmlResponse->locata;
        }
        if (!$locata && preg_match('/<locata>\s*([^<]+)\s*<\/locata>/i', $raw, $m)) {
            $locata = trim($m[1]);
        }
        if (!$locata) {
            Log::channel('itravex')->warning("⚠️ Bloqueo sin <locata> y sin errores formales.", [
                'sent_bloser_id' => $bloserId,
                'rooms' => $rooms,
            ]);
            return back()->withErrors(['El proveedor no devolvió localizador. Revisa que los IDs correspondan a la misma tarifa y vuelve a intentarlo.']);
        }

        // 🔹 OK
        session([
            'locata' => $locata,
            'ideses_created_at' => now(),
        ]);

        Log::channel('itravex')->info("📌 Locata almacenada en sesión: {$locata}");
        session()->flash('form_data', $data);

        return redirect()
            ->route('availability.lock.form')
            ->with('success', 'Bloqueo realizado correctamente.');
    }












    public function showLockForm(Request $request)
    {
        \Log::channel('itravex')->debug('showLockForm session snapshot', session()->only(['locata', 'success', 'form_data']));

        // Trae lo que ya tenías (form_data flasheado o lo que venga en la URL)
        $data = session('form_data', $request->all());

        // Si vienen distribuciones (distri) en la query, mézclalas con el resto
        if ($request->has('distri')) {
            $data['distri'] = $request->input('distri');
        }

        // Si no viene, pero en form_data no está, intenta recuperarlo de sesión
        if (!isset($data['distri']) && session()->has('distri')) {
            $data['distri'] = session('distri');
        }

        // Guarda en sesión para persistencia durante el bloqueo
        session(['distri' => $data['distri'] ?? []]);

        return view('availability.lock-form', [
            'data' => $data,
        ]);
    }



    public function closeReservation(Request $request)
    {
        $cfg = session('provider_cfg') ?? $this->getProviderConfig($request);
        if (empty($cfg['endpoint'])) {
            return back()->withErrors(['Endpoint no configurado (provider_cfg.endpoint está vacío).']);
        }

        $sessionId = session('ideses');
        $locata = session('locata');

        if (!$sessionId || !$locata) {
            return back()->withErrors(['Faltan datos para cerrar la reserva.']);
        }

        if (now()->diffInMinutes(session('ideses_created_at')) > 25) {
            session()->forget(['ideses', 'ideses_created_at', 'locata']);
            return back()->withErrors(['La sesión ha expirado. Realiza nuevamente la búsqueda.']);
        }

        $xml = <<<XML
<ReservaCerrarPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
  <locata>{$locata}</locata>
  <tipres>C</tipres>
</ReservaCerrarPeticion>
XML;

        Log::channel('itravex')->info("🔐 Petición ReservaCerrar enviada.");

        $response = Http::withBody($xml, 'application/xml')->post($cfg['endpoint']);

        Log::channel('itravex')->info("✅ Respuesta ReservaCerrar RAW:\n" . $response->body());


        if (!$response->successful()) {
            return back()->withErrors(['Error al cerrar la reserva con el proveedor.']);
        }

        $xmlResponse = simplexml_load_string($response->body());

        // Verificar error explícito
        if (isset($xmlResponse->error) || isset($xmlResponse->txterr)) {
            $mensaje = (string) ($xmlResponse->txterr ?? 'Error desconocido');
            return back()->withErrors(['Error al cerrar reserva: ' . $mensaje]);
        }

        // Nodos opcionales
        $resser = $xmlResponse->resser ?? null;
        $estsmo = $resser->estsmo ?? null;

        try {
            ItravexReservation::create([
                'locata' => $locata,
                'hotel_name' => $resser ? (string) $resser->nomser : null,
                'hotel_code' => $resser ? (string) $resser->codser : null,
                'room_type' => $estsmo ? (string) $estsmo->codsmo : null,
                'board' => $estsmo ? (string) $estsmo->codral : null,
                'start_date' => $resser && isset($resser->fecini)
                    ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', (string) $resser->fecini)->toDateString()
                    : null,
                'end_date' => $resser && isset($resser->fecfin)
                    ? \Carbon\Carbon::createFromFormat('d/m/Y H:i', (string) $resser->fecfin)->toDateString()
                    : null,
                'num_guests' => isset($xmlResponse->pasage->adl) ? count($xmlResponse->pasage->adl) : 0,
                'total_price' => $resser ? (float) $resser->impnoc : 0,
                'currency' => isset($xmlResponse->coddiv) ? (string) $xmlResponse->coddiv : null,
                'status' => $resser ? (string) $resser->cupest : null,
            ]);
        } catch (\Exception $e) {
            Log::channel('itravex')->error('❌ Error al guardar reserva: ' . $e->getMessage());
            return back()->withErrors(['Ocurrió un error al guardar la reserva.']);
        }

        // Limpieza de sesión de datos sensibles de la reserva (no borramos provider_cfg)
        session()->forget(['ideses', 'ideses_created_at', 'locata']);

        return redirect()
            ->route('availability.lock.form')
            ->with('success', 'Reserva cerrada y registrada correctamente.')
            ->with('form_data', session('form_data'));
    }

    public function cancelReservation(Request $request)
    {
        $request->validate([
            'locata' => 'required|string|max:30',
            'accion' => 'nullable|in:C,T,P',
            'motcan' => 'nullable|string|max:3',
            'descan' => 'nullable|string|max:1000',
        ]);

        $locataOriginal = $request->input('locata');
        $accion = $request->input('accion', 'C');
        $motcan = $request->input('motcan');
        $descan = $request->input('descan');

        $cfg = session('provider_cfg') ?? $this->getProviderConfig($request);
        if (empty($cfg['endpoint'])) {
            return back()->withErrors(['Endpoint no configurado (provider_cfg.endpoint está vacío).']);
        }

        $sessionId = $this->openSession($cfg);
        if (!$sessionId) {
            return back()->withErrors(['No se pudo abrir sesión con el proveedor.']);
        }

        $motcanXml = $motcan ? "<motcan>{$motcan}</motcan>" : '';
        $descanXml = $descan ? "<descan>{$descan}</descan>" : '';

        $xml = <<<XML
<ReservaCancelarPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
  <locata>{$locataOriginal}</locata>
  <accion>{$accion}</accion>
  {$motcanXml}
  {$descanXml}
</ReservaCancelarPeticion>
XML;

        try {
            Log::channel('itravex')->info("📤 Petición ReservaCancelar enviada.");

            $response = Http::withHeaders([
                'Content-Type' => 'application/xml',
            ])->timeout(30)->withBody($xml, 'application/xml')
                ->post($cfg['endpoint']);

            Log::channel('itravex')->info("✅ Respuesta ReservaCancelar recibida.");

            if (!$response->successful()) {
                return back()->withErrors(['Error al cancelar la reserva.']);
            }

            $xmlResponse = simplexml_load_string($response->body());

            // Si hay error
            if (isset($xmlResponse->coderr) || isset($xmlResponse->txterr)) {
                $mensaje = (string) ($xmlResponse->txterr ?? 'Error desconocido');
                return back()->withErrors(['Error del proveedor: ' . $mensaje]);
            }

            // Extraer datos
            $nuevoLocata = isset($xmlResponse->locata) ? (string) $xmlResponse->locata : $locataOriginal;
            $impcan = isset($xmlResponse->impcan) ? (float) $xmlResponse->impcan : null;
            $currency = isset($xmlResponse->coddiv) ? (string) $xmlResponse->coddiv : null;

            // Buscar y actualizar en la base de datos
            $reserva = \App\Models\ItravexReservation::where('locata', $locataOriginal)->first();

            if ($reserva) {
                $reserva->update([
                    'locata' => $nuevoLocata,
                    'status' => 'AN', // AN = Cancelada
                ]);
            }

            // Mensaje de confirmación
            $msg = 'Reserva cancelada correctamente.';
            if ($impcan !== null) {
                $msg .= " Importe de cancelación: {$impcan} {$currency}.";
            }

            return back()->with('success', $msg);
        } catch (\Exception $e) {
            Log::channel('itravex')->error('❌ Excepción en cancelación: ' . $e->getMessage());
            return back()->withErrors(['Excepción al cancelar: ' . $e->getMessage()]);
        }
    }

    public function showCancelForm()
    {
        return view('availability.cancel-reservation');
    }

    /**
     * Config del proveedor a usar en la petición actual.
     * Si no se envía por request, cae a config('itravex.*').
     */
    /**
     * Config del proveedor a usar en la petición actual.
     * Si un campo viene vacío en la request, cae a config('itravex.*').
     */
    private function getProviderConfig(Request $r): array
    {
        // helper: devuelve el valor del request si está relleno; si no, el config()
        $pick = function (string $field, string $configKey, ?callable $transform = null) use ($r) {
            $val = $r->filled($field) ? $r->input($field) : config($configKey);
            // normaliza strings vacíos por si acaso
            if (is_string($val)) {
                $val = trim($val);
            }
            if ($val === '' || $val === null) {
                $val = config($configKey);
            }
            if ($transform) {
                $val = $transform($val);
            }
            return $val;
        };

        return [
            'endpoint' => $pick('endpoint', 'itravex.endpoint'),
            'codsys'   => $pick('codsys',   'itravex.codsys', fn($v) => strtoupper((string)$v ?: 'XML')),
            'codage'   => $pick('codage',   'itravex.codage'),
            'user'     => $pick('user',     'itravex.user'),
            'pass'     => $pick('pass',     'itravex.pass'),
            'codtou'   => $r->filled('codtou') ? $r->input('codtou') : 'LIB',
        ];
    }
}
