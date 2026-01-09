<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\GiataProvider;

class SyncGiataProviders extends Command
{
    protected $signature = 'giata:sync-providers
        {--type=* : gds o tourOperator (se puede repetir)}
        {--provider= : Código del provider a sincronizar (ej: ors_beds o "ors beds")}';

    protected $description = 'Sincroniza lista de proveedores GIATA (GDS/TourOperators) o uno concreto si se indica --provider';

    public function handle(): int
    {
        // --- Normaliza provider (si viene) ---
        $providerFilter = $this->option('provider');
        $providerFilter = $providerFilter ? $this->normalizeProviderCode($providerFilter) : null;

        // --- Types ---
        $types = $this->option('type');

        // Si el usuario NO sabe el type y ha puesto provider:
        // probamos ambos en orden (puedes cambiar el orden si quieres)
        if ($providerFilter && empty($types)) {
            $types = ['tourOperator', 'gds'];
        }

        if (empty($types)) {
            $types = ['gds', 'tourOperator'];
        }

        $base = rtrim(config('services.giata.base_url', env('GIATA_BASE_URL', 'https://multicodes.giatamedia.com/webservice/rest/1.0')), '/');
        $user = config('services.giata.user', env('GIATA_USER'));
        $pass = config('services.giata.pass', env('GIATA_PASSWORD'));

        if (!$user || !$pass) {
            $this->error('Credenciales GIATA no encontradas (services.giata.* / .env). Revisa GIATA_USER y GIATA_PASSWORD.');
            return self::FAILURE;
        }

        $http = Http::withBasicAuth($user, $pass)
            ->accept('application/xml')
            ->timeout(90)
            ->connectTimeout(10)
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]])
            ->retry(3, 1500, throw: false);

        $foundOne = false;

        foreach ($types as $type) {
            $url = "{$base}/providers/{$type}";
            $this->info("Descargando: {$url}");

            $resp = $http->get($url);

            if ($resp->failed()) {
                $this->error("Fallo {$resp->status()} en {$url}");
                continue;
            }

            $xml = @simplexml_load_string($resp->body());
            if (!$xml) {
                $this->error("XML inválido en {$url}");
                continue;
            }

            $xml->registerXPathNamespace('xlink', 'http://www.w3.org/1999/xlink');

            $count = 0;
            foreach ($xml->provider as $p) {
                // Normalizamos el code del XML también para comparar
                $code  = $this->normalizeProviderCode((string)$p['providerCode']);
                $ptype = (string)$p['providerType'];

                // Si estamos en modo "uno concreto", filtramos
                if ($providerFilter && $code !== $providerFilter) {
                    continue;
                }

                $props = $p->properties;
                $attrs = $props->attributes('xlink', true);
                $href  = (string)($attrs['href'] ?? '');

                $requests = [];
                if (isset($props->requests->request)) {
                    foreach ($props->requests->request as $req) {
                        $params = [];
                        if (isset($req->params->param)) {
                            foreach ($req->params->param as $param) {
                                $params[] = [
                                    'name'     => (string)$param['name'],
                                    'typeHint' => (string)$param['typeHint'],
                                ];
                            }
                        }
                        $requests[] = [
                            'format' => trim((string)$req->format),
                            'params' => $params,
                        ];
                    }
                }

                GiataProvider::updateOrCreate(
                    ['provider_code' => $code],
                    [
                        'provider_type'   => $ptype ?: $type,
                        'properties_href' => $href,
                        'requests'        => $requests,
                    ]
                );

                $count++;
                $foundOne = true;

                // Si solo buscábamos UNO, terminamos ya (evita seguir descargando)
                if ($providerFilter) {
                    $this->info("✅ Provider '{$providerFilter}' creado/actualizado como '{$type}'.");
                    return self::SUCCESS;
                }
            }

            $this->info("{$type}: sincronizados {$count} proveedores.");
        }

        // Si pedían uno concreto y no apareció en ningún tipo
        if ($providerFilter && !$foundOne) {
            $this->error("❌ No se encontró el provider '{$providerFilter}' ni en tourOperator ni en gds.");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    private function normalizeProviderCode(string $raw): string
    {
        $s = trim(mb_strtolower($raw));
        // espacios -> underscore, múltiples underscores -> uno
        $s = preg_replace('/\s+/', '_', $s);
        $s = preg_replace('/_+/', '_', $s);
        return $s;
    }
}
