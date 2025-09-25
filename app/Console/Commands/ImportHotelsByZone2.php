<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Zone;
use App\Models\Hotel;

class ImportHotelsByZone2 extends Command
{
    protected $signature = 'import:hotels-static2';
    protected $description = 'Importa hoteles por zonas A- para cliente 2 usando InformacionServicioPeticion';

    public function handle()
    {
        $this->info('🚀 Iniciando importación de hoteles por zonas tipo A- (cliente 2)...');

        $sessionId = $this->openSession();
        if (!$sessionId) {
            $this->error('❌ No se pudo abrir sesión con Itravex (cliente 2).');
            return;
        }

        $this->line("🔑 Sesión activa: {$sessionId}");

        // Hoteles ya importados en cliente 2
        $importedZoneCodes = Hotel::on('mysql_cli2')->distinct()->pluck('zone_code')->toArray();

        $zones = Zone::on('mysql_cli2')
            ->where('is_final', true)
            ->where('code', 'like', 'A-%')
            ->whereNotIn('code', $importedZoneCodes)
            ->get();

        $total = 0;

        foreach ($zones as $zone) {
            $this->line("🌍 Zona: {$zone->code}");

            $xml = <<<XML
<InformacionServicioPeticion>
  <ideses>{$sessionId}</ideses>
  <codtou>LIB</codtou>
  <codzge>{$zone->code}</codzge>
  <codidi>ES</codidi>
</InformacionServicioPeticion>
XML;

            while (true) {
                try {
                    $response = Http::timeout(30)
                        ->withBody($xml, 'application/xml')
                        ->post(config('itravex.endpoint'));

                    if (!$response->successful()) {
                        $this->warn("  ⚠️ Error HTTP: {$response->status()} en zona {$zone->code}. Reintentando en 5 segundos...");
                        sleep(5);
                        continue;
                    }

                    $body = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $response->body());
                    $xmlData = simplexml_load_string($body);
                    $services = $xmlData->xpath('//servic');

                    if (!$services) {
                        $this->warn("  ⚠️ No se encontraron hoteles para la zona {$zone->code}.");
                        Storage::append('zonas_sin_hoteles_cliente2.txt', $zone->code);
                        break;
                    }

                    foreach ($services as $hotel) {
                        Hotel::on('mysql_cli2')->updateOrCreate(
                            ['codser' => (string) $hotel->codser],
                            [
                                'zone_code' => (string) ($hotel->serhot->codzge ?? $zone->code),
                                'name'      => (string) ($hotel->nomser ?? null),
                                'category'  => (string) ($hotel->codsca ?? null),
                                'type'      => (string) ($hotel->codtse ?? null),
                            ]
                        );
                        $total++;
                    }

                    $this->info("  ✅ Hoteles guardados para zona {$zone->code}: " . count($services));
                    break;

                } catch (ConnectionException $e) {
                    $this->warn("  🔁 Conexión reiniciada en zona {$zone->code}. Reintentando en 5 segundos...");
                    sleep(5);
                }
            }
        }

        $this->info("\n🎉 Importación finalizada (cliente 2). Total de hoteles guardados: {$total}");
    }

    private function openSession(): ?string
    {
        // 🔑 Credenciales cliente 2
        $codsys = 'XML';
        $codage = '7416';
        $user   = 'TRAVEL.ONE.XML';
        $pass   = 'TRAVEL.ONE.XML77';

        $xml = <<<XML
<SesionAbrirPeticion>
    <codsys>{$codsys}</codsys>
    <codage>{$codage}</codage>
    <idtusu>{$user}</idtusu>
    <pasusu>{$pass}</pasusu>
</SesionAbrirPeticion>
XML;

        $response = Http::withBody($xml, 'application/xml')
            ->post(config('itravex.endpoint'));

        if (!$response->successful()) {
            return null;
        }

        $xmlData = simplexml_load_string($response->body());

        return isset($xmlData->ideses) ? (string) $xmlData->ideses : null;
    }
}
