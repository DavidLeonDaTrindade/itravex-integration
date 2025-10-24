<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\GiataProvider;

class SyncGiataProviders extends Command
{
    protected $signature = 'giata:sync-providers {--type=* : gds o tourOperator (se puede repetir)}';
    protected $description = 'Sincroniza lista de proveedores GIATA (GDS/TourOperators)';

    public function handle(): int
    {
        $types = $this->option('type');
        if (empty($types)) {
            $types = ['gds','tourOperator'];
        }

        $base = rtrim(config('services.giata.base_url') ?? env('GIATA_BASE_URL'), '/');
        $user = config('services.giata.user');
        $pass = config('services.giata.pass');

        foreach ($types as $type) {
            $url = "{$base}/providers/{$type}";
            $this->info("Descargando: {$url}");

            $resp = Http::withBasicAuth($user, $pass)
                ->accept('application/xml')
                ->get($url);

            if ($resp->failed()) {
                $this->error("Fallo {$resp->status()} en {$url}");
                continue;
            }

            $xml = simplexml_load_string($resp->body());
            if (!$xml) {
                $this->error("XML inválido en {$url}");
                continue;
            }

            // Registrar namespace xlink para leer @xlink:href
            $xml->registerXPathNamespace('xlink','http://www.w3.org/1999/xlink');

            $count = 0;
            foreach ($xml->provider as $p) {
                $code = (string)$p['providerCode'];
                $ptype = (string)$p['providerType'];
                // properties/@xlink:href
                $props = $p->properties;
                $attrs = $props->attributes('xlink', true);
                $href  = (string)($attrs['href'] ?? '');

                // requests -> array [{format, params:[{name,typeHint}]}]
                $requests = [];
                if (isset($props->requests->request)) {
                    foreach ($props->requests->request as $req) {
                        $params = [];
                        if (isset($req->params->param)) {
                            foreach ($req->params->param as $param) {
                                $params[] = [
                                    'name' => (string)$param['name'],
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
                        'provider_type'   => $ptype ?: $type, // por si viene incoherente en el XML
                        'properties_href' => $href,
                        'requests'        => $requests,
                    ]
                );
                $count++;
            }

            $this->info("{$type}: sincronizados {$count} proveedores.");
        }

        return self::SUCCESS;
    }
}