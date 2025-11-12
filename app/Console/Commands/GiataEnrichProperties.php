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
        {--provider=itravex : Código del provider base para filtrar activos (ej. itravex)}
        {--since= : YYYY-MM-DD para sincronización incremental}
        {--country= : ISO2 (ej. ES, DE, US)}
        {--enrich-basics : Tras cada giataId, pedir el detalle y actualizar name/country}
        {--only-active : Solo upsert si el provider base tiene algún code ACTIVO en propertyCodes}
        {--save-codes : Guardar CODES ACTIVOS de *todos* los providers de la propiedad}
        {--providers= : (opcional) Lista coma-separada de providers a guardar; por defecto todos}
        {--refresh-codes : Antes de guardar, borrar los codes previos de ese giataId para los providers elegidos}
        {--sleep=100 : Micro-sleep en ms entre peticiones de detalle (anti-rate-limit)}';

    protected $description = 'Sincroniza GIATA properties. Filtra por activos del provider base (--only-active). Con --save-codes guarda los codes activos de TODOS los providers (o los indicados en --providers) para cada propiedad.';

    public function handle(): int
    {
        $baseProviderCode = strtolower($this->option('provider') ?: 'itravex'); // provider base para el filtro de activos
        $doEnrich         = (bool)$this->option('enrich-basics');
        $onlyActive       = (bool)$this->option('only-active');
        $saveCodes        = (bool)$this->option('save-codes');
        $refreshCodes     = (bool)$this->option('refresh-codes');
        $sleepMs          = (int)($this->option('sleep') ?? 100);

        // Filtro opcional de providers a guardar (para --save-codes)
        $providersFilter = $this->option('providers');
        $providersWanted = null; // null = todos
        if ($providersFilter !== null && trim($providersFilter) !== '') {
            $providersWanted = array_values(array_filter(array_map(
                fn($s) => strtolower(trim($s)),
                explode(',', $providersFilter)
            )));
        }

        // Provider base (para construir URL lista)
        /** @var GiataProvider|null $providerRow */
        $providerRow = GiataProvider::whereRaw('LOWER(provider_code)=?', [$baseProviderCode])->first();
        if (!$providerRow) {
            $this->error("No existe provider base '{$baseProviderCode}' en giata_providers.");
            return self::FAILURE;
        }

        $baseUrl = $providerRow->properties_href
            ?: "https://multicodes.giatamedia.com/webservice/rest/1.0/properties/gds/{$baseProviderCode}";

        $country = $this->option('country');
        $since   = $this->option('since');

        $url = rtrim($baseUrl, '/');
        if ($country) $url .= '/country/' . strtoupper($country);
        if ($since)   $url .= '/since/' . $since;

        // Auth
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

        $totalUpserts = 0;
        $totalCodes   = 0;
        $visited      = 0;

        // Cache de providers (provider_code => GiataProvider)
        $providersCache = [];
        $getProviderByCode = function(string $code) use (&$providersCache) {
            $key = strtolower($code);
            if (isset($providersCache[$key])) return $providersCache[$key];
            $row = GiataProvider::whereRaw('LOWER(provider_code)=?', [$key])->first();
            $providersCache[$key] = $row; // puede ser null si no existe
            return $row;
        };

        // Helpers XML
        $baseFilter = strtolower($baseProviderCode);
        $providerHasActiveCode = function(\SimpleXMLElement $property) use ($baseFilter): bool {
            $activeCodes = $property->xpath(
                ".//propertyCodes/provider[translate(@providerCode,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')='{$baseFilter}']" .
                "/code[not(@status) or translate(@status,'ABCDEFGHIJKLMNOPQRSTUVWXYZ','abcdefghijklmnopqrstuvwxyz')!='inactive']"
            );
            return !empty($activeCodes);
        };

        // Extrae TODOS los providers de la propiedad con sus codes ACTIVOS (filtrando opcionalmente por providersWanted)
        $extractActiveCodesAllProviders = function(\SimpleXMLElement $property) use ($providersWanted): array {
            $result = []; // [[providerCode, codeValue, status, addInfo], ...]
            // Todos los providers bajo propertyCodes
            $providers = $property->xpath(".//propertyCodes/provider");
            if (!$providers) return $result;

            foreach ($providers as $provNode) {
                $provCode = isset($provNode['providerCode']) ? strtolower((string)$provNode['providerCode']) : null;
                if (!$provCode) continue;

                // Si hay filtro de lista y este provider no está, saltar
                if (is_array($providersWanted) && !in_array($provCode, $providersWanted, true)) {
                    continue;
                }

                // Recorremos sus <code>
                foreach ($provNode->code ?? [] as $codeNode) {
                    $statusAttr = isset($codeNode['status']) ? strtolower((string)$codeNode['status']) : null;
                    if ($statusAttr === 'inactive') continue; // solo activos

                    $value = isset($codeNode->value) ? trim((string)$codeNode->value) : null;
                    if (!$value) continue;

                    $addInfoNode = $codeNode->addInfo ?? null;
                    $addInfo = null;
                    if ($addInfoNode) {
                        $addInfo = json_encode(self::simpleXmlToArrayStatic($addInfoNode), JSON_UNESCAPED_UNICODE);
                    }

                    $result[] = [
                        'provider_code' => $provCode,
                        'code_value'    => $value,
                        'status'        => 'active',
                        'add_info'      => $addInfo,
                    ];
                }
            }
            return $result;
        };

        // Paginación
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
                    if ($giataId <= 0) { $bar->advance(); continue; }

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
                                if ($href && isset($href[0])) $d = $http()->get((string)$href[0]);
                            }
                        }
                        if ($d->ok()) {
                            $parsed = @simplexml_load_string($d->body());
                            if ($parsed && isset($parsed->property)) $detailXml = $parsed;
                        } else {
                            $this->output->writeln("\n[WARN] Detail {$giataId} -> HTTP {$d->status()}");
                        }

                        // Filtro: solo propiedades con provider base ACTIVO
                        if ($onlyActive) {
                            $shouldUpsert = $detailXml ? $providerHasActiveCode($detailXml->property) : false;
                            if (!$shouldUpsert) {
                                $this->output->writeln("\n[SKIP] {$giataId} sin codes activos para provider base '{$baseProviderCode}'.");
                            }
                        }

                        if ($sleepMs > 0) usleep($sleepMs * 1000);
                    }

                    if ($shouldUpsert) {
                        // Upsert giata_properties
                        $attrs = [];
                        if ($lastUpd) { try { $attrs['last_update'] = Carbon::parse($lastUpd); } catch (\Throwable $e) {} }
                        $propRow = GiataProperty::updateOrCreate(['giata_id' => $giataId], $attrs);
                        $totalUpserts++;

                        // Enriquecer básicos
                        if ($doEnrich) {
                            if (!$detailXml) {
                                $detailUrl = str_replace('/1.0/', '/1.latest/', $base . '/properties/' . $giataId);
                                $d2 = $http()->get($detailUrl);
                                if (!$d2->ok() && in_array($d2->status(), [301, 302], true)) {
                                    $xml301b = @simplexml_load_string($d2->body());
                                    if ($xml301b) {
                                        $xml301b->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');
                                        $href = $xml301b->xpath('//description/@xlink:href');
                                        if ($href && isset($href[0])) $d2 = $http()->get((string)$href[0]);
                                    }
                                }
                                if ($d2->ok()) $detailXml = @simplexml_load_string($d2->body());
                                if ($sleepMs > 0) usleep($sleepMs * 1000);
                            }
                            if ($detailXml && isset($detailXml->property)) {
                                $name    = isset($detailXml->property->name) ? (string)$detailXml->property->name : null;
                                $country = isset($detailXml->property->country) ? strtoupper((string)$detailXml->property->country) : null;
                                $basic = [];
                                if ($name)    $basic['name']    = $name;
                                if ($country) $basic['country'] = $country;
                                if ($basic) GiataProperty::where('giata_id', $giataId)->update($basic);
                            }
                        }

                        // Guardar codes activos de TODOS los providers (o los indicados)
                        if ($saveCodes) {
                            if (!$detailXml) {
                                $detailUrl = str_replace('/1.0/', '/1.latest/', $base . '/properties/' . $giataId);
                                $d3 = $http()->get($detailUrl);
                                if ($d3->ok()) $detailXml = @simplexml_load_string($d3->body());
                                if ($sleepMs > 0) usleep($sleepMs * 1000);
                            }

                            if ($detailXml && isset($detailXml->property)) {
                                $allCodes = $extractActiveCodesAllProviders($detailXml->property);

                                // Determinar provider_ids a refrescar (si procede)
                                if ($refreshCodes && !empty($allCodes)) {
                                    $providerIdsToClear = [];
                                    foreach ($allCodes as $row) {
                                        $provRow = $getProviderByCode($row['provider_code']);
                                        if ($provRow) $providerIdsToClear[$provRow->id] = true;
                                    }
                                    if (!empty($providerIdsToClear)) {
                                        GiataPropertyCode::where('giata_property_id', $propRow->id)
                                            ->whereIn('provider_id', array_keys($providerIdsToClear))
                                            ->delete();
                                    }
                                }

                                // Upsert de payload
                                if (!empty($allCodes)) {
                                    $now = now();
                                    $payload = [];
                                    foreach ($allCodes as $c) {
                                        $provRow = $getProviderByCode($c['provider_code']);
                                        if (!$provRow) {
                                            $this->output->writeln("\n[WARN] Provider '{$c['provider_code']}' no existe en giata_providers. Se omite su code '{$c['code_value']}'.");
                                            continue;
                                        }
                                        $payload[] = [
                                            'giata_property_id' => $propRow->id,
                                            'provider_id'       => $provRow->id,
                                            'code_value'        => $c['code_value'],
                                            'status'            => $c['status'] ?? 'active',
                                            'add_info'          => $c['add_info'] ?? null,
                                            'created_at'        => $now,
                                            'updated_at'        => $now,
                                        ];
                                    }

                                    if (!empty($payload)) {
                                        DB::table('giata_property_codes')->upsert(
                                            $payload,
                                            ['giata_property_id', 'provider_id', 'code_value'],
                                            ['status', 'add_info', 'updated_at']
                                        );
                                        $totalCodes += count($payload);
                                    }
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
                if ($attrs && isset($attrs['href'])) $next = (string)$attrs['href'];
            }
            $current = $next;
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Listo. Visitadas {$visited} entradas, upserts en giata_properties: {$totalUpserts}.");
        if ($saveCodes) $this->info("Codes insertados/actualizados: {$totalCodes}.");

        if ($doEnrich)   $this->line('Enriquecimiento básico activado (--enrich-basics).');
        if ($onlyActive) $this->line("Filtro de activos por provider base '{$baseProviderCode}' activado (--only-active).");
        if ($saveCodes)  $this->line('Guardado de codes activo (--save-codes).' . ($providersWanted ? ' Solo los providers: '.implode(', ', $providersWanted) : ' Todos los providers.') . ($refreshCodes ? ' Con limpieza previa (--refresh-codes).' : ''));

        return self::SUCCESS;
    }

    // === Utils ===

    private static function simpleXmlToArrayStatic(\SimpleXMLElement $xml): array
    {
        $json = json_encode($xml, JSON_UNESCAPED_UNICODE);
        $arr  = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }
}
