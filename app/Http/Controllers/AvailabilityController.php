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
    public function checkAvailability(Request $request)
    {
        $validated = $request->validate([
            'fecini'       => 'required|date',
            'fecfin'       => 'required|date|after_or_equal:fecini',
            'codzge'       => 'required_without:hotel_codes|string|nullable',
            'hotel_codes'  => 'required_without:codzge|string|nullable',
            'numadl'       => 'required|integer|min:1',
            'page'         => 'sometimes|integer|min:1',
            'mode'         => 'sometimes|in:fast,full',
            // Opcionales
            'timeout'      => 'nullable|integer|min:1000|max:60000',
            'numrst'       => 'nullable|integer|min:1|',
            'batch_size'   => 'nullable|integer|min:1|max:200',
            'per_page'     => 'sometimes|integer|min:1|max:500',
            // Credenciales/endpoint opcionales
            'endpoint'     => 'sometimes|url|nullable',
            'codsys'       => 'sometimes|string|nullable',
            'codage'       => 'sometimes|string|nullable',
            'user'         => 'sometimes|string|nullable',
            'pass'         => 'sometimes|string|nullable',
            'codtou'       => 'sometimes|string|nullable',
            // País ISO 3166-1 (2 o 3 letras)
            'codnac'       => 'nullable|string|min:2|max:3',
        ]);

        // Config elegida (request -> config)
        $cfg = $this->getProviderConfig($request);
        if (empty($cfg['endpoint'])) {
            return response()->json(['error' => 'Endpoint no configurado (provider_cfg.endpoint está vacío)'], 500);
        }
        session(['provider_cfg' => $cfg]);

        $mode       = $request->input('mode', 'fast');
        $complete   = ($mode === 'full');

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

        // Abrir sesión
        $sessionId = $this->openSession($cfg);
        if (!$sessionId) {
            return response()->json(['error' => 'No se pudo abrir sesión con el proveedor'], 500);
        }
        session(['ideses' => $sessionId, 'ideses_created_at' => now()]);

        $fecini = date('d/m/Y', strtotime($validated['fecini']));
        $fecfin = date('d/m/Y', strtotime($validated['fecfin']));

        // Pool “histórico” (lo sigo dejando en perf, pero ya no limita el branch codser)
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

        // Parseo lista manual (si viene)
        $manualCodes = [];
        if (!empty($validated['hotel_codes'])) {
            $raw = preg_split('/[,\s]+/u', trim($validated['hotel_codes']));
            $manualCodes = array_values(array_filter(array_unique(array_map('trim', $raw)), fn($v) => $v !== ''));
        }

        // ¿Usar modo ZONA nativo del proveedor?
        $usingZoneMode = empty($manualCodes) && !empty($validated['codzge']);

        // Concurrency/Timeouts comunes
        $maxConcurrency = $complete ? 8  : 10;
        $connectTimeout = $complete ? 1.0 : 0.7;
        $requestTimeout = $complete ? 8.0 : 3.5;
        $earlyStop      = ($mode !== 'full');

        // margen +2s para evitar cURL 28 cuando timeoutMs es justo
        if ($timeoutMs) {
            $requestTimeout = max($requestTimeout, min(60.0, ($timeoutMs / 1000.0) + 2.0));
        }

        $endpoint = $cfg['endpoint'];

        // Métricas/red comunes
        $reqMetrics   = [];
        $firstHeaders = null;
        $totalBytes   = 0;

        // Contenedores
        $allHotels            = [];
        $totalRooms           = 0;
        $internalRateCount    = 0;
        $externalRateCount    = 0;
        $externalALWRates     = 0;
        $providerRateCounts   = [];
        $hotelRateCounts      = [];
        $providerHotelSets    = [];

        // NUEVO: contenedores para payloads
        $allPayloads   = [];   // todos los XML enviados
        $payloadMeta   = [];   // metadatos de batches y totales
        $payloadPreview = null; // preview ampliado (hasta 5)
        $firstPayload = null;  // compat con la vista actual
        $zoneTotalHotels = 0;

        // Helper retry (usado en modo zona)
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

        // ---------- MODO ZONA nativo ----------
        if ($usingZoneMode) {
            $t_db0 = microtime(true);
            $zoneTotalHotels = \App\Models\Hotel::where('zone_code', $validated['codzge'])->count();
            $perf['db_ms'] += (int) round((microtime(true) - $t_db0) * 1000);

            $buildXmlZone = function (int $indpag) use ($sessionId, $cfg, $fecini, $fecfin, $validated, $timeoutMs, $numrst, $codnac) {
                $traduc    = "  <traduc>codsmo#</traduc>\n  <traduc>codcha#</traduc>";
                $numrstXml = $numrst ? "  <numrst>{$numrst}</numrst>\n" : "  <numrst>200</numrst>\n";
                $codnacXml = $codnac ? "  <codnac>{$codnac}</codnac>\n" : "";
                $codzge    = e($validated['codzge']);
                return <<<XML
<DisponibilidadHotelPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
  <fecini>{$fecini}</fecini>
  <fecfin>{$fecfin}</fecfin>
  <codzge>{$codzge}</codzge>
  <distri id="1">
    <numuni>1</numuni>
    <numadl>{$validated['numadl']}</numadl>
  </distri>
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
                // NUEVO: guardar cada payload para el preview/listado
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
                        $isInternal = isset($room->codtrf) || isset($room->inftrf);
                        $codtou     = (string) $room->codtou;

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

                        $rooms[] = [
                            'room_type'        => (string) $room->codsmo,
                            'room_code'        => (string) $room->codcha,
                            'board'            => (string) $room->codral,
                            'price_per_night'  => $price,
                            'availability'     => (string) $room->cupinv,
                            'room_internal_id' => (string) $room['id'],
                            'is_internal'      => $isInternal,
                            'codtou'           => $codtou,
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
                if ($earlyStop && $receivedHotels >= $perPage) break;
            } while (true);

            $perf['http_ms'] = (int) round((microtime(true) - $t_http0) * 1000);
            if (!empty($note)) {
                $firstHeaders = $firstHeaders ?? [];
                $firstHeaders['note'] = $note;
            }

            // Meta para payloads (zona)
            $payloadMeta = [
                'mode'          => 'zone',
                'batches'       => count($allPayloads),
                'showing'       => 0, // se rellena más abajo al construir el preview
                'codser_total'  => null, // en zona no mandamos codser
                'zone'          => $validated['codzge'] ?? null,
            ];
        }
        // ---------- MODO POR LISTA/CODSER (AHORA SIN PERDER NINGUNO) ----------
        else {
            $t_db0 = microtime(true);

            // 1) Construir la lista COMPLETA de codser (sin skip/take)
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
                    ],
                    'providers'           => [],
                    'hotelProviderMatrix' => [],
                    // NUEVO: payloads vacíos en este early return
                    'firstPayload'  => null,
                    'payloads'      => [],
                    'payload_meta'  => ['mode' => 'codes', 'batches' => 0, 'showing' => 0, 'codser_total' => 0],
                ]);
            }

            // 2) Peticiones en lotes de 100 codser (sin dejar ninguno)
            $client = new \GuzzleHttp\Client([
                'headers' => [
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Connection'      => 'keep-alive',
                    'Content-Type'    => 'application/xml',
                ],
                'curl' => [CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_2_0],
            ]);

            $buildXmlMulti = function (array $codsers) use ($sessionId, $cfg, $fecini, $fecfin, $validated, $timeoutMs, $numrst, $codnac) {
                $codserLines = implode("\n", array_map(fn($c) => "  <codser>{$c}</codser>", $codsers));
                $traduc      = "  <traduc>codsmo#</traduc>\n  <traduc>codcha#</traduc>\n  <traduc>codral#</traduc>";
                $numrstXml   = $numrst ? "  <numrst>{$numrst}</numrst>\n" : "";
                $codnacXml   = $codnac ? "  <codnac>{$codnac}</codnac>\n" : "";
                return <<<XML
<DisponibilidadHotelPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
  <fecini>{$fecini}</fecini>
  <fecfin>{$fecfin}</fecfin>
{$codserLines}
  <distri id="1">
    <numuni>1</numuni>
    <numadl>{$validated['numadl']}</numadl>
  </distri>
{$traduc}
{$codnacXml}  <timout>{$timeoutMs}</timout>
{$numrstXml}  <indpag>0</indpag>
  <chkscm>S</chkscm>
</DisponibilidadHotelPeticion>
XML;
            };

            // 🔢 Tamaño fijo 100 por requisito
            $batchSizeCodser = 50;
            $chunks = array_chunk($codesPool, $batchSizeCodser, false);
            $perf['batches_sent'] = count($chunks);

            // NUEVO: guardar TODOS los payloads para el preview
            foreach ($chunks as $i => $chunk) {
                $allPayloads[] = $buildXmlMulti($chunk);
            }

            // Ejecutamos TODOS los batches (para no dejar ninguno), con límite de concurrencia
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
                'fulfilled' => function ($response) use (&$firstHeaders, &$totalBytes, &$allHotels, &$totalRooms, &$internalRateCount, &$externalRateCount, &$externalALWRates, &$providerRateCounts, &$hotelRateCounts, &$providerHotelSets) {
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
                            $isInternal = isset($room->codtrf) || isset($room->inftrf);
                            $codtou     = (string) $room->codtou;

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

                            $rooms[] = [
                                'room_type'        => (string) $room->codsmo,
                                'room_code'        => (string) $room->codcha,
                                'board'            => (string) $room->codral,
                                'price_per_night'  => $price,
                                'availability'     => (string) $room->cupinv,
                                'room_internal_id' => (string) $room['id'],
                                'is_internal'      => $isInternal,
                                'codtou'           => $codtou,
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

            // Meta para payloads (códigos)
            $payloadMeta = [
                'mode'          => 'codes',
                'batches'       => count($allPayloads),
                'showing'       => 0, // se rellena más abajo al construir el preview
                'codser_total'  => count($codesPool),
                'zone'          => $validated['codzge'] ?? null,
            ];
        }

        // ---------- TAIL COMÚN: ordenar, paginar, métricas y salida ----------
        if (!empty($allHotels)) {
            usort($allHotels, fn($a, $b) => ($a['price'] <=> $b['price']) ?: strcmp($a['name'], $b['name']));
        }

        if (!isset($offsetWithinBlock)) $offsetWithinBlock = 0;

        // Paginar sobre TODO lo recuperado
        $availableTotal = count($allHotels);
        $lastPage = max(1, (int) ceil($availableTotal / $perPage));
        if ($currentPage > $lastPage) {
            $currentPage = $lastPage;
        }

        $offset = ($currentPage - 1) * $perPage;
        $visibleHotels = array_slice($allHotels, $offset, $perPage);

        // Conteos de la página
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

        // NUEVO: construir preview ampliado (concatena hasta 5 payloads con separadores)
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

        // Compat: la vista actual espera 'firstPayload'. Le daremos el preview ampliado.
        $firstPayload = $payloadPreview;

        // Matriz proveedor
        $scope = $request->query('scope', 'page') === 'block' ? 'block' : 'page';
        $hotelsForMatrix = ($scope === 'block') ? $allHotels : $visibleHotels;

        $providers = array_keys($providerRateCounts ?? []);
        sort($providers);
        $hotelProviderMatrix = [];
        foreach ($hotelsForMatrix as $h) {
            $rowCounts = array_fill_keys($providers, 0);
            foreach ($h['rooms'] as $room) {
                $p = (string) ($room['codtou'] ?? '');
                if ($p === '') continue;
                if (!array_key_exists($p, $rowCounts)) {
                    $rowCounts[$p] = 0;
                    if (!in_array($p, $providers, true)) $providers[] = $p;
                }
                $rowCounts[$p]++;
            }
            $hotelProviderMatrix[] = ['name' => $h['name'], 'code' => $h['code'], 'counts' => $rowCounts];
        }
        sort($providers);
        foreach ($hotelProviderMatrix as &$row) {
            foreach ($providers as $prov) if (!isset($row['counts'][$prov])) $row['counts'][$prov] = 0;
            ksort($row['counts']);
        }
        unset($row);

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
                ],
                'providers'             => $providers,
                'hotel_provider_matrix' => $hotelProviderMatrix,
                // NUEVO: payloads y meta/preview en JSON también
                'payloads'              => $allPayloads,
                'payload_meta'          => $payloadMeta,
                'payload_preview'       => $payloadPreview,
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
            ],
            'providers'            => $providers,
            'hotelProviderMatrix'  => $hotelProviderMatrix,
            // NUEVO: preview ampliado y todos los payloads
            'firstPayload'         => $firstPayload,   // preview concatenado (hasta 5)
            'payloads'             => $allPayloads,    // todos los XML (uno por batch/página)
            'payload_meta'         => $payloadMeta,    // metadatos para UI
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
        Log::channel('itravex')->info("⚠️ Entrando a submitLock");

        // Validación básica (sin contract_type porque NO lo enviaremos)
        $data = $request->validate([
            'hotel_code'        => 'required|string',   // codser (no lo enviamos en el XML de bloqueo)
            'birthdates'        => 'required|array',
            'birthdates.*'      => 'required|date',
            'codzge'            => 'required|string',
            'start_date'        => 'required|date',
            'end_date'          => 'required|date',
            'hotel_name'        => 'required|string',
            'board'             => 'required|string',
            'price_per_night'   => 'required',
            'currency'          => 'required|string',
            'room_internal_id'  => 'required|string',   // puede ser "3#57" y lo respetaremos tal cual
            'hotel_internal_id' => 'required|string',   // p.ej. "3"
        ]);

        // Config proveedor
        $cfg = session('provider_cfg') ?? $this->getProviderConfig($request);
        if (empty($cfg['endpoint'])) {
            return back()->withErrors(['Endpoint no configurado (provider_cfg.endpoint está vacío).']);
        }

        // Sesión con el proveedor
        $sessionId = session('ideses');
        if (!$sessionId) {
            $sessionId = $this->openSession($cfg);
            if (!$sessionId) {
                return back()->withErrors(['No se pudo abrir sesión']);
            }
            session(['ideses' => $sessionId, 'ideses_created_at' => now()]);
        }

        // Pasajeros (formato dd/mm/YYYY)
        $adlXml = '';
        $pasidXml = '';
        foreach ($data['birthdates'] as $i => $birthdate) {
            $id = $i + 1;
            $adlXml  .= "<adl id=\"{$id}\"><fecnac>" . date('d/m/Y', strtotime($birthdate)) . "</fecnac></adl>";
            $pasidXml .= "<pasid>{$id}</pasid>";
        }

        // OJO: replicamos el patrón que funcionó:
        // - bloser@id = hotel_internal_id tal cual
        // - disssmo@id = room_internal_id tal cual (permitiendo "3#57")
        // - NO enviamos fecini/fecfin/codser/tipcon/tarifi
        $bloserId = (string) $data['hotel_internal_id'];
        $dissmoId = (string) $data['room_internal_id'];

        $xml = <<<XML
<BloqueoServicioPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>{$cfg['codtou']}</codtou>
    <tipcon>C</tipcon> <!-- 🔧 AÑADIDO -->
  <pasage>{$adlXml}</pasage>
  <bloser id="{$bloserId}">
    <dissmo id="{$dissmoId}">
      <numuni>1</numuni>
      {$pasidXml}
    </dissmo>
  </bloser>
  <tipcon>V</tipcon>
  <accion>A</accion>
</BloqueoServicioPeticion>
XML;


        Log::channel('itravex')->debug("🔵 XML de petición BloqueoServicio:\n" . $xml);


        $response = Http::withHeaders([
            'Accept-Encoding' => 'gzip',
            'Content-Type'    => 'application/xml',
        ])->timeout(30)->withBody($xml, 'application/xml')
            ->post($cfg['endpoint']);

        if (!$response->successful()) {
            Log::channel('itravex')->error("❌ HTTP fallo en BloqueoServicio", ['status' => $response->status()]);
            return back()->withErrors(['Error de conexión con el proveedor']);
        }

        $raw = $response->body();
        Log::channel('itravex')->info("🟢 Respuesta BloqueoServicio RAW:\n" . $raw);

        $xmlResponse = @simplexml_load_string($raw);
        if ($xmlResponse === false) {
            Log::channel('itravex')->error("❌ No se pudo parsear XML de BloqueoServicio");
            return back()->withErrors(['Respuesta inválida del proveedor']);
        }

        // Errores de negocio
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

        // Extraer LOCATA
        $locata = null;
        try {
            $nodes = $xmlResponse->xpath('//locata');
            if (!empty($nodes) && isset($nodes[0])) {
                $locata = trim((string)$nodes[0]);
            }
        } catch (\Throwable $e) {
        }

        if (!$locata) {
            if (isset($xmlResponse->resser->locata)) {
                $locata = (string)$xmlResponse->resser->locata;
            } elseif (isset($xmlResponse->resser->estsmo->locata)) {
                $locata = (string)$xmlResponse->resser->estsmo->locata;
            } elseif (isset($xmlResponse->locata)) {
                $locata = (string)$xmlResponse->locata;
            }
        }

        if (!$locata && preg_match('/<locata>\s*([^<]+)\s*<\/locata>/i', $raw, $m)) {
            $locata = trim($m[1]);
        }

        if (!$locata) {
            Log::channel('itravex')->warning("⚠️ Bloqueo sin <locata> y sin errores formales.", [
                'sent_bloser_id' => $bloserId,
                'sent_dissmo_id' => $dissmoId,
            ]);
            return back()->withErrors(['El proveedor no devolvió localizador. Revisa que los IDs correspondan a la misma tarifa y vuelve a intentarlo.']);
        }

        // OK
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
        return view('availability.lock-form', [
            'data' => session('form_data', $request->all())
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
