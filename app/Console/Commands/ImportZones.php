<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;


class ImportZones extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:zones';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa zonas geográficas desde la API de Itravex';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📦 Importando zonas desde la API de Itravex...');

        // Puedes regenerar la sesión automáticamente si lo prefieres
        $sessionId = 'XML#26360#870020370650001';
        // Sustituye si es dinámica

        $xml = <<<XML
<DisponibilidadDestinoPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>LIB</codtou>
  <tiparb>ZHT</tiparb>
</DisponibilidadDestinoPeticion>
XML;

        $response = Http::withBody($xml, 'application/xml')
            ->post(config('itravex.endpoint'));

        if (!$response->successful()) {
            $this->error('❌ Error al obtener las zonas: ' . $response->status());
            return;
        }
        $this->line("🔧 XML recibido:");
        $body = $response->body();
        file_put_contents(storage_path('app/zones_response.xml'), $body);
        $this->line("🗂 XML guardado en storage/app/zones_response.xml");

        $xmlData = simplexml_load_string($body);

        if (!$xmlData) {
            $this->error('❌ La respuesta XML no es válida.');
            return;
        }

        $zones = $xmlData->xpath('//infzge');

        if (!$zones) {
            $this->warn('⚠️ No se encontraron zonas en la respuesta.');
            return;
        }

        foreach ($zones as $zone) {
            \App\Models\Zone::updateOrCreate(
                ['code' => (string) $zone->codzge],
                [
                    'parent_code' => (string) $zone->zgesup ?: null,
                    'type' => (string) $zone->tipzge,
                    'name' => trim((string) $zone->nomzge) ?: null,
                    'is_final' => ((string)$zone->chkfin === 'S'),
                ]
            );
        }

        $this->info('✅ Zonas importadas correctamente. Total: ' . count($zones));
    }
}
