<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ImportZones2 extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:zones2';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Importa zonas geográficas desde la API de Itravex usando credenciales del cliente 2';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('📦 Importando zonas desde la API de Itravex (cliente 2)...');

        // 🔑 Abrir sesión con credenciales del cliente 2
        $sessionXml = <<<XML
<SesionAbrirPeticion>
    <codsys>XML</codsys>
    <codage>7416</codage>
    <idtusu>TRAVEL.ONE.XML</idtusu>
    <pasusu>TRAVEL.ONE.XML77</pasusu>
    <codidi>EN</codidi>
</SesionAbrirPeticion>
XML;

        $sessionResponse = Http::withBody($sessionXml, 'application/xml')
            ->post(config('itravex.endpoint'));

        if (!$sessionResponse->successful()) {
            $this->error('❌ Error al abrir sesión: ' . $sessionResponse->status());
            return;
        }

        $sessionData = simplexml_load_string($sessionResponse->body());
        if (!$sessionData || !isset($sessionData->ideses)) {
            $this->error('❌ No se pudo obtener el id de sesión.');
            return;
        }

        $sessionId = (string) $sessionData->ideses;
        $this->info("🔑 Sesión abierta correctamente. ID: {$sessionId}");

        // 📥 Petición de zonas
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

        $body = $response->body();
        file_put_contents(storage_path('app/zones2_response.xml'), $body);
        $this->line("🗂 XML guardado en storage/app/zones2_response.xml");

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

        // 💾 Guardar en la base de datos cliente2
        foreach ($zones as $zone) {
            \App\Models\Zone::on('mysql_cli2')->updateOrCreate(
                ['code' => (string) $zone->codzge],
                [
                    'parent_code' => (string) $zone->zgesup ?: null,
                    'type'        => (string) $zone->tipzge,
                    'name'        => trim((string) $zone->nomzge) ?: null,
                    'is_final'    => ((string)$zone->chkfin === 'S'),
                ]
            );
        }

        $this->info('✅ Zonas importadas correctamente en cliente2. Total: ' . count($zones));
    }
}
