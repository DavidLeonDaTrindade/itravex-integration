<?php

namespace App\Console\Commands;

use App\Models\GiataProvider;
use App\Models\GiataProperty;
use App\Models\GiataPropertyCode;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GiataSyncProperties extends Command
{
    protected $signature = 'giata:sync-properties
        {--provider= : Código(s) del provider base para filtrar activos, separados por coma (ej. itravex,restel). Si no se indica, usa la lista fija.}
        {--since= : YYYY-MM-DD para sincronización incremental}
        {--country= : ISO2 (ej. ES, DE, US)}
        {--enrich-basics : Tras cada giataId, pedir el detalle y actualizar name/country}
        {--only-active : Solo upsert si el provider base tiene algún code ACTIVO en propertyCodes}
        {--save-codes : Guardar CODES ACTIVOS de *todos* los providers de la propiedad}
        {--providers= : (opcional) Lista coma-separada de providers a guardar; por defecto todos}
        {--refresh-codes : Antes de guardar, borrar los codes previos de ese giataId para los providers elegidos}
        {--sleep=100 : Micro-sleep en ms entre peticiones de detalle (anti-rate-limit)}';

    protected $description = 'Sincroniza GIATA properties para uno o varios providers base. Si no se indica --provider, recorre una lista fija de providers. Filtra por activos del provider base (--only-active). Con --save-codes guarda los codes activos de TODOS los providers (o los indicados en --providers) para cada propiedad.';

    public function handle(): int
    {
        // Lista fija de providers que quieres sincronizar si no se pasa --provider
        $defaultProviders = [
            'babylon_holiday',
            'barcelo',
            'cn_travel',
            'connectycs',
            'darinaholidays',
            'DOTW',
            'dts_dieuxtravelservices',
            'gekko_infinite',
            'gekko_teldar',
            'guestincoming',
            'hotelbook',
            'hyperguest',
            'iol_iwtx',
            'itravex',
            'logitravel_dr',
            'methabook2',
            'mikitravel',
            'opentours',
            'ors_beds',
            'paximum',
            'ratehawk2',
            'restel',
            'solole',
            'sunhotels',
            'travellanda',
            'veturis',
            'w2m',
            'wl2t',
            'yalago',
            'travco',
        ];

        $doEnrich     = (bool)$this->option('enrich-basics');
        $onlyActive   = (bool)$this->option('only-active');
        $saveCodes    = (bool)$this->option('save-codes');
        $refreshCodes = (bool)$this->option('refresh-codes');
        $sleepMs      = (int)($this->option('sleep') ?? 100);

        // Filtro opcional de providers cuyos CODES se guardarán (cuando --save-codes)
        $providersFilter = $this->option('providers');
        $providersWanted = null; // null = todos
        if ($providersFilter !== null && trim($providersFilter) !== '') {
            $providersWanted = array_values(array_filter(array_map(
                fn($s) => strtolower(trim($s)),
                explode(',', $providersFilter)
            )));
        }

        $country = $this->option('country');
        $since   = $this->option('since');

        // Auth GIATA
        $base = rtrim(config('services.giata.base_url', env('GIATA_BASE_URL', 'https://multicodes.giatamedia.com/webservice/rest/1.0')), '/');
        $user = config('services.giata.user', env('GIATA_USER'));
        $pass = config('services.giata.pass', env('GIATA_PASSWORD'));
        if (!$user || !$pass) {
            $this->error('Faltan credenciales GIATA (config/services.php o .env).');
            return self::FAILURE;
        }
        $authHeader = 'Basic ' . base64_encode("{$user}:{$pass}");

        $http = fn() => Http::withHeaders(['Authorization' => $authHeader])
            ->accept('application/xml')
            ->timeout(90)
            ->connectTimeout(10)
            ->retry(3, 1500, throw: false)
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]);

        // 🔥 PERF: Mapa rápido provider_code -> provider_id (evita queries dentro del loop)
        $providersByCode = GiataProvider::select('id', 'provider_code')
            ->get()
            ->mapWithKeys(fn($p) => [strtolower($p->provider_code) => (int)$p->id])
            ->all();

        // Determinar qué providers base vamos a procesar
        $providerOpt = $this->option('provider');

        if ($providerOpt !== null && trim($providerOpt) !== '') {
            $providerCodes = collect(explode(',', $providerOpt))
                ->map(fn($p) => strtolower(trim($p)))
                ->filter()
                ->unique()
                ->values()
                ->all();
        } else {
            $providerCodes = array_map('strtolower', $defaultProviders);
        }

        if (empty($providerCodes)) {
            $this->error('No se ha especificado ningún provider base válido y la lista fija está vacía.');
            return self::FAILURE;
        }

        $this->info('Providers base a procesar: ' . implode(', ', $providerCodes));
        $this->newLine();

        foreach ($providerCodes as $baseProviderCode) {
            $this->info(str_repeat('=', 60));
            $this->info("Procesando provider base: {$baseProviderCode}");
            $this->info(str_repeat('=', 60));

            /** @var GiataProvider|null $providerRow */
            $providerRow = GiataProvider::whereRaw('LOWER(provider_code)=?', [$baseProviderCode])->first();
            if (!$providerRow) {
                $this->error("No existe provider base '{$baseProviderCode}' en giata_providers.");
                return self::FAILURE;
            }

            $baseProviderId = $providersByCode[strtolower($baseProviderCode)] ?? null;

            $baseUrl = $providerRow->properties_href
                ?: "https://multicodes.giatamedia.com/webservice/rest/1.0/properties/gds/{$baseProviderCode}";

            $url = rtrim($baseUrl, '/');
            if ($country) {
                $url .= '/country/' . strtoupper($country);
            }
            if ($since) {
                $url .= '/since/' . $since;
            }

            $totalUpserts = 0;
            $totalCodes   = 0;
            $visited      = 0;

            // 🔥 PERF: si estamos guardando SOLO codes del provider base (ej: --providers=allbeds),
            // cargamos los giataIds ya procesados y los saltamos (resume).
            $skipGiataIds = [];
            if ($saveCodes && $baseProviderId && is_array($providersWanted) && count($providersWanted) === 1) {
                $only = strtolower($providersWanted[0]);
                if ($only === strtolower($baseProviderCode)) {
                    $this->info("Cargando giataIds ya procesados para '{$baseProviderCode}' (resume)...");
                    DB::table('giata_property_codes as gpc')
                        ->join('giata_properties as gp', 'gp.id', '=', 'gpc.giata_property_id')
                        ->where('gpc.provider_id', $baseProviderId)
                        ->select('gp.giata_id')
                        ->orderBy('gp.giata_id')
                        ->chunk(5000, function ($rows) use (&$skipGiataIds) {
                            foreach ($rows as $r) {
                                $skipGiataIds[(int)$r->giata_id] = true;
                            }
                        });
                    $this->info("Resume: cargados " . count($skipGiataIds) . " giataIds ya procesados.");
                }
            }

            // Helpers XML específicos para ESTE provider base
            $baseFilter = strtolower($baseProviderCode);

            $providerHasActiveCode = function (\SimpleXMLElement $property) use ($baseFilter): bool {
                $activeCodes = $property->xpath(
                    ".//propertyCodes/provider[translate(@providerCode,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$baseFilter}']" .
                    "/code[not(@status) or translate(@status,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')!='inactive']"
                );
                return !empty($activeCodes);
            };

            // Extrae providers/codes ACTIVOS (filtrando opcionalmente por providersWanted)
            // 🔥 PERF: NO guardamos add_info (muy caro y no suele aportar valor)
            $extractActiveCodesAllProviders = function (\SimpleXMLElement $property) use ($providersWanted): array {
                $result = [];
                $providers = $property->xpath(".//propertyCodes/provider");
                if (!$providers) {
                    return $result;
                }

                foreach ($providers as $provNode) {
                    $provCode = isset($provNode['providerCode']) ? strtolower((string)$provNode['providerCode']) : null;
                    if (!$provCode) {
                        continue;
                    }

                    if (is_array($providersWanted) && !in_array($provCode, $providersWanted, true)) {
                        continue;
                    }

                    foreach ($provNode->code ?? [] as $codeNode) {
                        $statusAttr = isset($codeNode['status']) ? strtolower((string)$codeNode['status']) : null;
                        if ($statusAttr === 'inactive') {
                            continue;
                        }

                        $value = isset($codeNode->value) ? trim((string)$codeNode->value) : null;
                        if (!$value) {
                            continue;
                        }

                        $result[] = [
                            'provider_code' => $provCode,
                            'code_value'    => $value,
                            'status'        => 'active',
                            'add_info'      => null, // PERF: no almacenar
                        ];
                    }
                }

                return $result;
            };

            // 🔥 PERF: UPSERT por lotes
            $codesBatch = [];
            $codesBatchSize = 2000;

            // Paginación para ESTE provider base
            $current = $url;
            $bar = $this->output->createProgressBar();
            $bar->start();

            while ($current) {
                $resp = $http()->get($current);
                if (!$resp->ok()) {
                    $this->newLine();
                    $this->error("HTTP {$resp->status()} al pedir: {$current}");
                    return self::FAILURE;
                }

                $xml = @simplexml_load_string($resp->body());
                if (!$xml) {
                    $this->newLine();
                    $this->error("No se pudo parsear XML en: {$current}");
                    return self::FAILURE;
                }
                $xml->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');

                if (isset($xml->property)) {
                    foreach ($xml->property as $prop) {
                        $visited++;

                        $giataId = (int)($prop['giataId'] ?? 0);
                        $lastUpd = (string)($prop['lastUpdate'] ?? '');
                        if ($giataId <= 0) {
                            $bar->advance();
                            continue;
                        }

                        // ✅ Resume skip (solo cuando hicimos preload)
                        if (!empty($skipGiataIds) && isset($skipGiataIds[$giataId])) {
                            $bar->advance();
                            continue;
                        }

                        $shouldUpsert = true;
                        $detailXml = null;

                        // Detalle si hace falta
                        if ($onlyActive || $doEnrich || $saveCodes) {
                            $detailUrl = str_replace('/1.0/', '/1.latest/', $base . '/properties/' . $giataId);

                            $d = $http()->get($detailUrl);
                            if (!$d->ok() && in_array($d->status(), [301, 302], true)) {
                                $xml301 = @simplexml_load_string($d->body());
                                if ($xml301) {
                                    $xml301->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
                                    $href = $xml301->xpath('//description/@xlink:href');
                                    if ($href && isset($href[0])) {
                                        $d = $http()->get((string)$href[0]);
                                    }
                                }
                            }

                            if ($d->ok()) {
                                $parsed = @simplexml_load_string($d->body());
                                if ($parsed && isset($parsed->property)) {
                                    $detailXml = $parsed;
                                }
                            } else {
                                $this->output->writeln("\n[WARN] Detail {$giataId} -> HTTP {$d->status()}");
                            }

                            if ($onlyActive) {
                                $shouldUpsert = $detailXml ? $providerHasActiveCode($detailXml->property) : false;
                                if (!$shouldUpsert) {
                                    $this->output->writeln("\n[SKIP] {$giataId} sin codes activos para provider base '{$baseProviderCode}'.");
                                }
                            }

                            if ($sleepMs > 0) {
                                usleep($sleepMs * 1000);
                            }
                        }

                        if ($shouldUpsert) {
                            $attrs = [];
                            if ($lastUpd) {
                                try {
                                    $attrs['last_update'] = Carbon::parse($lastUpd);
                                } catch (\Throwable $e) {
                                    // ignore
                                }
                            }

                            // Upsert property
                            $propRow = GiataProperty::updateOrCreate(['giata_id' => $giataId], $attrs);
                            $totalUpserts++;

                            // Enrich basics (si lo pides)
                            if ($doEnrich) {
                                if (!$detailXml) {
                                    $detailUrl = str_replace('/1.0/', '/1.latest/', $base . '/properties/' . $giataId);
                                    $d2 = $http()->get($detailUrl);
                                    if (!$d2->ok() && in_array($d2->status(), [301, 302], true)) {
                                        $xml301b = @simplexml_load_string($d2->body());
                                        if ($xml301b) {
                                            $xml301b->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
                                            $href = $xml301b->xpath('//description/@xlink:href');
                                            if ($href && isset($href[0])) {
                                                $d2 = $http()->get((string)$href[0]);
                                            }
                                        }
                                    }
                                    if ($d2->ok()) {
                                        $detailXml = @simplexml_load_string($d2->body());
                                    }
                                    if ($sleepMs > 0) {
                                        usleep($sleepMs * 1000);
                                    }
                                }

                                if ($detailXml && isset($detailXml->property)) {
                                    $name  = isset($detailXml->property->name) ? (string)$detailXml->property->name : null;
                                    $ctry  = isset($detailXml->property->country) ? strtoupper((string)$detailXml->property->country) : null;
                                    $basic = [];
                                    if ($name) $basic['name'] = $name;
                                    if ($ctry) $basic['country'] = $ctry;

                                    if ($basic) {
                                        GiataProperty::where('giata_id', $giataId)->update($basic);
                                    }
                                }
                            }

                            // Save codes (si lo pides)
                            if ($saveCodes) {
                                if (!$detailXml) {
                                    $detailUrl = str_replace('/1.0/', '/1.latest/', $base . '/properties/' . $giataId);
                                    $d3 = $http()->get($detailUrl);
                                    if ($d3->ok()) {
                                        $detailXml = @simplexml_load_string($d3->body());
                                    }
                                    if ($sleepMs > 0) {
                                        usleep($sleepMs * 1000);
                                    }
                                }

                                if ($detailXml && isset($detailXml->property)) {
                                    $allCodes = $extractActiveCodesAllProviders($detailXml->property);

                                    // Refresh: borrar codes previos SOLO de providers presentes en este payload (si se activa)
                                    if ($refreshCodes && !empty($allCodes)) {
                                        $providerIdsToClear = [];
                                        foreach ($allCodes as $row) {
                                            $pid = $providersByCode[strtolower($row['provider_code'])] ?? null;
                                            if ($pid) $providerIdsToClear[$pid] = true;
                                        }
                                        if (!empty($providerIdsToClear)) {
                                            GiataPropertyCode::where('giata_property_id', $propRow->id)
                                                ->whereIn('provider_id', array_keys($providerIdsToClear))
                                                ->delete();
                                        }
                                    }

                                    if (!empty($allCodes)) {
                                        $now = now();
                                        foreach ($allCodes as $c) {
                                            $provCode = strtolower($c['provider_code']);
                                            $provId = $providersByCode[$provCode] ?? null;

                                            if (!$provId) {
                                                $this->output->writeln("\n[WARN] Provider '{$c['provider_code']}' no existe en giata_providers. Se omite su code '{$c['code_value']}'.");
                                                continue;
                                            }

                                            $codesBatch[] = [
                                                'giata_property_id' => $propRow->id,
                                                'provider_id'       => $provId,
                                                'code_value'        => $c['code_value'],
                                                'status'            => $c['status'] ?? 'active',
                                                'add_info'          => $c['add_info'] ?? null,
                                                'created_at'        => $now,
                                                'updated_at'        => $now,
                                            ];

                                            if (count($codesBatch) >= $codesBatchSize) {
                                                DB::table('giata_property_codes')->upsert(
                                                    $codesBatch,
                                                    ['giata_property_id', 'provider_id', 'code_value'],
                                                    ['status', 'add_info', 'updated_at']
                                                );
                                                $totalCodes += count($codesBatch);
                                                $codesBatch = [];
                                            }
                                        }
                                    }

                                    // ✅ Marcar este giataId como hecho para no repetir dentro del mismo run
                                    if (!empty($skipGiataIds)) {
                                        $skipGiataIds[$giataId] = true;
                                    }
                                }
                            }
                        }

                        $bar->advance();
                    }
                }

                // Paginación
                $next = null;
                $more = $xml->xpath('//more');
                if ($more && isset($more[0])) {
                    $attrs = $more[0]->attributes('xlink', true);
                    if ($attrs && isset($attrs['href'])) {
                        $next = (string)$attrs['href'];
                    }
                }
                $current = $next;
            }

            // 🔥 PERF: flush final del batch
            if (!empty($codesBatch)) {
                DB::table('giata_property_codes')->upsert(
                    $codesBatch,
                    ['giata_property_id', 'provider_id', 'code_value'],
                    ['status', 'add_info', 'updated_at']
                );
                $totalCodes += count($codesBatch);
                $codesBatch = [];
            }

            $bar->finish();
            $this->newLine(2);
            $this->info("Provider base {$baseProviderCode}: Visitadas {$visited} entradas, upserts en giata_properties: {$totalUpserts}.");
            if ($saveCodes) {
                $this->info("Provider base {$baseProviderCode}: Codes insertados/actualizados: {$totalCodes}.");
            }

            if ($doEnrich) {
                $this->line('Enriquecimiento básico activado (--enrich-basics).');
            }
            if ($onlyActive) {
                $this->line("Filtro de activos por provider base '{$baseProviderCode}' activado (--only-active).");
            }
            if ($saveCodes) {
                $this->line(
                    'Guardado de codes activo (--save-codes).'
                    . ($providersWanted ? ' Solo los providers: ' . implode(', ', $providersWanted) : ' Todos los providers.')
                    . ($refreshCodes ? ' Con limpieza previa (--refresh-codes).' : '')
                );
            }

            $this->newLine(2);
        }

        return self::SUCCESS;
    }

    private static function simpleXmlToArrayStatic(\SimpleXMLElement $xml): array
    {
        $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
        $arr  = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }
}
