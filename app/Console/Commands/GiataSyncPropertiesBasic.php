<?php

namespace App\Console\Commands;

use App\Models\GiataProvider;
use App\Models\GiataProperty;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;


class GiataSyncPropertiesBasic extends Command
{
    // Usa el nombre que Artisan ya reconoce: giata:sync-basic
    protected $signature = 'giata:sync-basic
        {--provider= : Código(s) del provider, separados por coma (ej. itravex,restel)}
        {--all : Procesar la lista fija de providers GIATA}
        {--since= : YYYY-MM-DD para sincronización incremental}
        {--country= : ISO2 (ej. ES, DE, US)}
        {--enrich-basics : (reservado) }
        {--sleep=100 : Micro-sleep en ms entre peticiones de detalle (anti-rate-limit)}';

    protected $description = 'Sincroniza la lista de GIATA properties de uno o varios proveedores (gds/tourOperator), insertando solo nuevos giata_id (sin duplicar) y enriqueciendo name/country para todos los nuevos.';

    public function handle(): int
    {
        // Lista fija de providers que necesitas
        $defaultProviders = [
            'abbey_lc',
            'abbey_tp',
            'allbeds',
            'alturadestinationservices',
            'ask2travel',
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

        // 1) Determinar lista de providers a procesar
        if ($this->option('all')) {
            // Usar lista fija
            $providerCodes = collect($defaultProviders)
                ->map(fn($p) => strtolower(trim($p)))
                ->filter()
                ->unique()
                ->values();
        } else {
            // Usar lo que venga por --provider (puede ser uno o varios separados por coma)
            $providerOpt = $this->option('provider') ?: 'itravex';

            $providerCodes = collect(explode(',', $providerOpt))
                ->map(fn($p) => strtolower(trim($p)))
                ->filter()
                ->unique()
                ->values();
        }

        if ($providerCodes->isEmpty()) {
            $this->error('No se ha especificado ningún provider válido.');
            return self::FAILURE;
        }

        $sleepMs  = (int)($this->option('sleep') ?? 100);
        $country  = $this->option('country');
        $since    = $this->option('since');

        $this->info('Providers a procesar: ' . $providerCodes->implode(', '));
        $this->newLine();

        $globalStatus = self::SUCCESS;

        foreach ($providerCodes as $providerCode) {
            $this->info("▶ Procesando provider: {$providerCode}");
            $status = $this->syncProvider($providerCode, $sleepMs, $country, $since);
            $this->newLine(2);

            if ($status !== self::SUCCESS) {
                $globalStatus = self::FAILURE;
                $this->error("❌ Provider {$providerCode} terminó con errores.");
            } else {
                $this->info("✅ Provider {$providerCode} procesado correctamente.");
            }

            $this->newLine();
        }

        return $globalStatus;
    }

    /**
     * Sincroniza GIATA properties para un provider concreto.
     * - Si giata_id ya existe: se ignora.
     * - Si es nuevo: se inserta + se pide detalle para rellenar name/country.
     * - OPTIMIZADO: whereIn() en chunks por página.
     */
    protected function syncProvider(string $providerCode, int $sleepMs, ?string $country, ?string $since): int
    {
        // 1) Buscar provider en BD para tomar su properties_href si existe
        /** @var GiataProvider|null $provider */
        $provider = GiataProvider::where('provider_code', $providerCode)->first();

        $baseUrl = $provider?->properties_href
            ?: "https://multicodes.giatamedia.com/webservice/rest/1.0/properties/gds/{$providerCode}";

        // 2) Modificadores (country/since)
        $url = rtrim($baseUrl, '/');
        if ($country) {
            $url .= '/country/' . strtoupper($country);
        }
        if ($since) {
            $url .= '/since/' . $since;
        }

        // 3) Auth GIATA (HTTP Basic) usando config() con fallback a env()
        $base = rtrim(config('services.giata.base_url', env('GIATA_BASE_URL', 'https://multicodes.giatamedia.com/webservice/rest/1.0')), '/');
        $user = config('services.giata.user', env('GIATA_USER'));
        $pass = config('services.giata.pass', env('GIATA_PASSWORD'));
        if (!$user || !$pass) {
            $this->error('Faltan credenciales GIATA (revisa config/services.php o .env).');
            return self::FAILURE;
        }
        $authHeader = 'Basic ' . base64_encode("{$user}:{$pass}");

        // Cliente HTTP base
        $http = fn() => Http::withHeaders(['Authorization' => $authHeader])
            ->accept('application/xml')
            ->timeout(300)
            ->connectTimeout(10)
            ->retry(2, 1000, throw: false)
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]);


        $totalInserted = 0;
        $visited       = 0;

        // 4) Iterar lista + paginación con <more xlink:href="...">
        $current = $url;
        $bar = $this->output->createProgressBar();
        $bar->start();

        while ($current) {
            $resp = $http()->get($current);

            if (!$resp->ok()) {
                $this->newLine();
                $this->error("HTTP {$resp->status()} al pedir: $current");
                $bar->finish();
                $this->newLine();
                return self::FAILURE;
            }

            $xml = @simplexml_load_string($resp->body());
            if (!$xml) {
                $this->newLine();
                $this->error("No se pudo parsear XML en: $current");
                $bar->finish();
                $this->newLine();
                return self::FAILURE;
            }

            // registrar namespace xlink (para leer <more>)
            $xml->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');

            // --- preconsultar qué giata_id de ESTA PÁGINA ya existen EN CHUNKS ---
            $idsPagina = [];
            if (isset($xml->property)) {
                foreach ($xml->property as $prop) {
                    $giataId = (int)($prop['giataId'] ?? 0);
                    if ($giataId > 0) {
                        $idsPagina[] = $giataId;
                    }
                }
            }

            $existingIds = [];
            if (!empty($idsPagina)) {
                // troceamos en bloques de 500 (puedes subirlo a 1000 si ves que va bien)
                foreach (array_chunk($idsPagina, 500) as $chunk) {
                    $chunkExisting = GiataProperty::whereIn('giata_id', $chunk)
                        ->pluck('giata_id')
                        ->all();

                    if (!empty($chunkExisting)) {
                        $existingIds = array_merge($existingIds, $chunkExisting);
                    }
                }
            }
            $existingSet = !empty($existingIds) ? array_flip($existingIds) : [];

            // 4.1) Procesar <property .../>
            if (isset($xml->property)) {
                foreach ($xml->property as $prop) {
                    $visited++;

                    $giataId = (int)($prop['giataId'] ?? 0);
                    $lastUpd = (string)($prop['lastUpdate'] ?? '');

                    if ($giataId <= 0) {
                        $bar->advance();
                        continue;
                    }

                    // si ya existe ese giata_id en la tabla, lo saltamos por completo
                    if (isset($existingSet[$giataId])) {
                        $bar->advance();
                        continue;
                    }

                    // Insert base: giata_id + last_update
                    $attrs = [
                        'giata_id' => $giataId,
                    ];

                    if ($lastUpd) {
                        try {
                            $attrs['last_update'] = Carbon::parse($lastUpd);
                        } catch (\Throwable $e) {
                            // ignorar parseo incorrecto
                        }
                    }

                    GiataProperty::create($attrs);
                    $totalInserted++;

                    $detailUrl = $base . '/properties/' . $giataId;
                    $detailUrl = str_replace('/1.0/', '/1.latest/', $detailUrl);

                    try {
                        $d = $http()->get($detailUrl);
                    } catch (ConnectionException $e) {
                        // Timeout o problema de red: avisamos y seguimos con el siguiente hotel
                        $this->warn("⏱ Timeout al pedir detalle GIATA {$giataId}: {$e->getMessage()}");
                        if ($sleepMs > 0) {
                            usleep($sleepMs * 1000);
                        }
                        $bar->advance();
                        continue; // pasar al siguiente property
                    }

                    if ($d->ok()) {
                        $dx = @simplexml_load_string($d->body());
                        if ($dx && isset($dx->property)) {
                            $name        = isset($dx->property->name) ? (string)$dx->property->name : null;
                            $countryCode = isset($dx->property->country) ? strtoupper((string)$dx->property->country) : null;

                            $basic = [];
                            if ($name !== null && $name !== '')               $basic['name'] = $name;
                            if ($countryCode !== null && $countryCode !== '') $basic['country'] = $countryCode;

                            if ($basic) {
                                GiataProperty::where('giata_id', $giataId)->update($basic);
                            }
                        }
                    }


                    // pequeño sleep anti rate-limit (para las llamadas de detalle)
                    if ($sleepMs > 0) {
                        usleep($sleepMs * 1000);
                    }

                    $bar->advance();
                }
            }

            // 4.2) Paginación: buscar nodo <more xlink:href="...">
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

        $bar->finish();
        $this->newLine(2);
        $this->info("Provider {$providerCode}: Visitadas {$visited} entradas, NUEVAS insertadas: {$totalInserted} (con name/country).");

        return self::SUCCESS;
    }
}
