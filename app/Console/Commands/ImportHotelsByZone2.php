<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Client\ConnectionException;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
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

        // Conexión directa a itravex2
        $conn = DB::connection('mysql_cli2');

        // Opcional: si quieres saltar zonas que YA tienen al menos un hotel
        $importedZoneCodes = $conn->table('hotels')->distinct()->pluck('zone_code')->toArray();

        // Si quieres recorrer TODAS las zonas finales A- (recomendado al menos 1 pasada completa), quita el whereNotIn
        $zonesQuery = $conn->table('zones')
            ->where('is_final', true)
            ->where('code', 'like', 'A-%');

        // Mantener este filtro SOLO si de verdad quieres saltar zonas ya importadas
        if (!empty($importedZoneCodes)) {
            $zonesQuery->whereNotIn('code', $importedZoneCodes);
        }

        $zones = $zonesQuery->get();

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

                    // Normaliza entidades rotas
                    $body = preg_replace('/&(?!amp;|lt;|gt;|quot;|apos;)/', '&amp;', $response->body());
                    $xmlData = simplexml_load_string($body);

                    if ($xmlData === false) {
                        $this->warn("  ⚠️ XML no válido para la zona {$zone->code}. Reintentando en 5 segundos...");
                        sleep(5);
                        continue;
                    }

                    $services = $xmlData->xpath('//servic');

                    if (!$services || count($services) === 0) {
                        $this->warn("  ⚠️ No se encontraron hoteles para la zona {$zone->code}.");
                        Storage::append('zonas_sin_hoteles_cliente2.txt', $zone->code);
                        break;
                    }

                    // Transacción por zona (opcional, pero recomendado)
                    $conn->beginTransaction();

                    $insertadosZona = 0;
                    foreach ($services as $hotel) {
                        $codser = (string) ($hotel->codser ?? '');
                        if ($codser === '') {
                            continue; // sin clave primaria no podemos upsert
                        }

                        $payload = [
                            'zone_code' => (string) ($hotel->serhot->codzge ?? $zone->code),
                            'name'      => trim((string) ($hotel->nomser ?? '')) ?: null,
                            'category'  => (string) ($hotel->codsca ?? null),
                            'type'      => (string) ($hotel->codtse ?? null),
                            'updated_at' => now(),
                        ];

                        // Si quieres timestamps “completos” al insertar:
                        // - updateOrInsert no distingue insert/update para 'created_at'
                        // - truco: setéalo siempre; en updates se sobreescribirá también
                        $payload['created_at'] = now();

                        $conn->table('hotels')->updateOrInsert(
                            ['codser' => $codser], // clave única
                            $payload
                        );

                        $insertadosZona++;
                        $total++;
                    }

                    $conn->commit();

                    $this->info("  ✅ Hoteles guardados para zona {$zone->code}: {$insertadosZona}");
                    break;
                } catch (ConnectionException $e) {
                    $this->warn("  🔁 Conexión reiniciada en zona {$zone->code}. Reintentando en 5 segundos...");
                    sleep(5);
                } catch (\Throwable $e) {
                    // Revertimos si algo peta en la transacción
                    try {
                        $conn->rollBack();
                    } catch (\Throwable $ignore) {
                    }
                    $this->error("  ❌ Error en zona {$zone->code}: {$e->getMessage()}");
                    // Decide si quieres romper o continuar con la siguiente zona
                    break;
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
