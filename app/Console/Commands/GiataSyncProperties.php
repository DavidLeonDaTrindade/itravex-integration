<?php

namespace App\Console\Commands;

use App\Models\GiataProvider;
use App\Models\GiataProperty;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GiataSyncPropertiesBasic extends Command
{
    protected $signature = 'giata:sync-properties:basic
        {--provider=itravex : Código del provider (ej. itravex)}
        {--since= : YYYY-MM-DD para sincronización incremental}
        {--country= : ISO2 (ej. ES, DE, US)}
        {--enrich-basics : Tras cada giataId, pedir el detalle y actualizar name/country}
        {--sleep=100 : Micro-sleep en ms entre peticiones de detalle (anti-rate-limit)}';

    protected $description = 'Sincroniza la lista de GIATA properties de un proveedor (gds/tourOperator), guarda giata_id/last_update y, opcionalmente, enriquece name/country desde el detalle.';

    public function handle(): int
    {
        $providerCode  = strtolower($this->option('provider') ?: 'itravex');
        $doEnrich      = (bool)$this->option('enrich-basics');
        $sleepMs       = (int)($this->option('sleep') ?? 100);

        // 1) Buscar provider en BD para tomar su properties_href si existe
        /** @var GiataProvider|null $provider */
        $provider = GiataProvider::where('provider_code', $providerCode)->first();

        $baseUrl = $provider?->properties_href
            ?: "https://multicodes.giatamedia.com/webservice/rest/1.0/properties/gds/{$providerCode}";

        // 2) Modificadores (country/since)
        $country = $this->option('country');
        $since   = $this->option('since');

        $url = rtrim($baseUrl, '/');
        if ($country) {
            $url .= "/country/" . strtoupper($country);
        }
        if ($since) {
            $url .= "/since/" . $since;
        }

        // 3) Auth GIATA (HTTP Basic) usando config() con fallback a env()
        $base = rtrim(config('services.giata.base_url', env('GIATA_BASE_URL', 'https://multicodes.giatamedia.com/webservice/rest/1.0')), '/');
        $user = config('services.giata.user', env('GIATA_USER'));       // "giata|itravex.es"
        $pass = config('services.giata.pass', env('GIATA_PASSWORD'));   // "iTravex2022"
        if (!$user || !$pass) {
            $this->error('Faltan credenciales GIATA (revisa config/services.php o .env).');
            return self::FAILURE;
        }
        $authHeader = 'Basic ' . base64_encode("{$user}:{$pass}");

        // Cliente HTTP base
        $http = fn() => Http::withHeaders(['Authorization' => $authHeader])
            ->accept('application/xml')
            ->timeout(90)
            ->connectTimeout(10)
            ->retry(3, 1500, throw: false)
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]);

        $totalUpserts = 0;
        $visited = 0;

        // 4) Iterar lista + paginación con <more xlink:href="...">
        $current = $url;
        $bar = $this->output->createProgressBar();
        $bar->start();

        while ($current) {
            $resp = $http()->get($current);

            if (!$resp->ok()) {
                $this->newLine();
                $this->error("HTTP {$resp->status()} al pedir: $current");
                return self::FAILURE;
            }

            $xml = @simplexml_load_string($resp->body());
            if (!$xml) {
                $this->newLine();
                $this->error("No se pudo parsear XML en: $current");
                return self::FAILURE;
            }

            // registrar namespace xlink (para leer <more>)
            $xml->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');

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

                    // Upsert base: giata_id + last_update
                    $attrs = [];
                    if ($lastUpd) {
                        try {
                            $attrs['last_update'] = Carbon::parse($lastUpd);
                        } catch (\Throwable $e) { /* ignore */ }
                    }

                    GiataProperty::updateOrCreate(['giata_id' => $giataId], $attrs);
                    $totalUpserts++;

                    // (Opcional) Enriquecer básicos (name/country) llamando al detalle
                    if ($doEnrich) {
                        $detailUrl = $base . '/properties/' . $giataId;
                        // asegurar 1.latest
                        $detailUrl = str_replace('/1.0/', '/1.latest/', $detailUrl);

                        $d = $http()->get($detailUrl);
                        if ($d->ok()) {
                            $dx = @simplexml_load_string($d->body());
                            if ($dx && isset($dx->property)) {
                                $name    = isset($dx->property->name) ? (string)$dx->property->name : null;
                                $country = isset($dx->property->country) ? strtoupper((string)$dx->property->country) : null;

                                $basic = [];
                                if ($name !== null && $name !== '')       $basic['name'] = $name;
                                if ($country !== null && $country !== '') $basic['country'] = $country;
                                if ($basic) {
                                    GiataProperty::where('giata_id', $giataId)->update($basic);
                                }
                            }
                        }
                        // pequeño sleep anti rate-limit
                        if ($sleepMs > 0) {
                            usleep($sleepMs * 1000);
                        }
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
        $this->info("Listo. Visitadas {$visited} entradas, upserts: {$totalUpserts}.");

        if ($doEnrich) {
            $this->line('Enriquecimiento básico (name/country) activado.');
        } else {
            $this->line('Sugerencia: añade --enrich-basics para poblar name/country durante la sincronización.');
        }

        return self::SUCCESS;
    }
}
