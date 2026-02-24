<?php

namespace App\Console\Commands;

use App\Models\GiataProperty;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class GiataEnrichNullBasics extends Command
{
    protected $signature = 'giata:enrich-null-basics
        {--giata-id= : Enriquecer solo un giata_id concreto (debug)}
        {--limit=0 : Máximo de filas a procesar (0 = sin límite)}
        {--sleep=100 : Sleep ms entre llamadas (anti-rate-limit)}
        {--only-name : Solo rellenar name}
        {--only-country : Solo rellenar country}
        {--dry-run : No guarda en BD, solo muestra qué haría}
    ';

    protected $description = 'Rellena name/country en giata_properties cuando están NULL consultando /1.latest/properties/{giataId}.';

    public function handle(): int
    {
        $sleepMs = (int)($this->option('sleep') ?? 100);
        $limit   = (int)($this->option('limit') ?? 0);
        $dryRun  = (bool)$this->option('dry-run');

        $onlyName    = (bool)$this->option('only-name');
        $onlyCountry = (bool)$this->option('only-country');
        if (!$onlyName && !$onlyCountry) {
            // por defecto, ambos
            $onlyName = $onlyCountry = true;
        }

        $singleGiataId = $this->option('giata-id');
        if ($singleGiataId !== null && trim((string)$singleGiataId) !== '') {
            $singleGiataId = (int)$singleGiataId;
        } else {
            $singleGiataId = null;
        }

        // Auth GIATA
        $base = 'https://multicodes.giatamedia.com/webservice/rest';
        $user = config('services.giata.user', env('GIATA_USER'));
        $pass = config('services.giata.pass', env('GIATA_PASSWORD'));

        if (!$user || !$pass) {
            $this->error('Faltan credenciales GIATA (config/services.php o .env).');
            return self::FAILURE;
        }

        $authHeader = 'Basic ' . base64_encode("{$user}:{$pass}");

        $http = fn() => Http::withHeaders(['Authorization' => $authHeader])
            ->accept('application/xml')
            ->timeout(120)
            ->connectTimeout(20)
            ->retry(3, 2000, throw: false)
            ->withOptions(['curl' => [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]]);

        // Query base: filas con NULL
        $q = GiataProperty::query()
            ->select(['id', 'giata_id', 'name', 'country', 'last_update'])
            ->when($singleGiataId, fn($qq) => $qq->where('giata_id', $singleGiataId))
            ->where(function ($qq) use ($onlyName, $onlyCountry) {
                $qq->where(function ($q2) use ($onlyName) {
                    if ($onlyName) $q2->whereNull('name');
                });
                $qq->orWhere(function ($q2) use ($onlyCountry) {
                    if ($onlyCountry) $q2->whereNull('country');
                });
            })
            ->orderBy('id');

        $this->info("Enrich NULL basics: " . ($dryRun ? "DRY-RUN" : "WRITE") . ".");
        if ($singleGiataId) $this->info("Modo single giata_id={$singleGiataId}.");
        if ($limit > 0) $this->info("Limit={$limit}.");
        $this->info("Sleep={$sleepMs}ms. Fields: " . ($onlyName ? "name " : "") . ($onlyCountry ? "country" : ""));
        $this->newLine();

        $processed = 0;
        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar();
        $bar->start();

        $q->chunkById(500, function ($rows) use (
            &$processed, &$updated, &$skipped, &$errors,
            $http, $sleepMs, $limit, $dryRun, $onlyName, $onlyCountry, $bar, $base
        ) {
            foreach ($rows as $row) {
                if ($limit > 0 && $processed >= $limit) {
                    return false; // corta chunkById
                }

                $processed++;
                $giataId = (int)$row->giata_id;

                // Endpoint detalle (como tú usas)
                $detailUrl = rtrim($base, '/') . "/1.latest/properties/{$giataId}";

                $resp = $http()->get($detailUrl);
                if (!$resp->ok()) {
                    $errors++;
                    $this->output->writeln("\n[WARN] giataId={$giataId} HTTP {$resp->status()}");
                    if ($sleepMs > 0) usleep($sleepMs * 1000);
                    $bar->advance();
                    continue;
                }

                $xml = @simplexml_load_string($resp->body());
                if (!$xml || !isset($xml->property)) {
                    $errors++;
                    $this->output->writeln("\n[WARN] giataId={$giataId} XML inválido");
                    if ($sleepMs > 0) usleep($sleepMs * 1000);
                    $bar->advance();
                    continue;
                }

                $p = $xml->property;

                $newName = isset($p->name) ? trim((string)$p->name) : null;
                $newCountry = isset($p->country) ? strtoupper(trim((string)$p->country)) : null;

                $payload = [];

                if ($onlyName && $row->name === null && $newName) {
                    $payload['name'] = $newName;
                }
                if ($onlyCountry && $row->country === null && $newCountry) {
                    $payload['country'] = $newCountry;
                }

                // opcional: sincronizar last_update si viene y no está
                $lastUpd = (string)($p['lastUpdate'] ?? '');
                if ($lastUpd) {
                    try {
                        $payload['last_update'] = Carbon::parse($lastUpd);
                    } catch (\Throwable $e) {
                        // ignore
                    }
                }

                if (empty($payload)) {
                    $skipped++;
                } else {
                    if ($dryRun) {
                        $updated++;
                        $this->output->writeln("\n[DRY] giataId={$giataId} update: " . json_encode($payload, JSON_UNESCAPED_UNICODE));
                    } else {
                        GiataProperty::where('id', $row->id)->update($payload);
                        $updated++;
                    }
                }

                if ($sleepMs > 0) usleep($sleepMs * 1000);
                $bar->advance();
            }

            return true;
        });

        $bar->finish();
        $this->newLine(2);

        $this->info("Procesadas: {$processed}");
        $this->info("Actualizadas: {$updated}");
        $this->info("Sin cambios: {$skipped}");
        $this->info("Errores: {$errors}");

        return self::SUCCESS;
    }
}
