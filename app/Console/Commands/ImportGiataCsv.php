<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\GiataPropertyRaw;

class ImportGiataCsv extends Command
{
    protected $signature = 'giata:import-raw-csv {file}';
    protected $description = 'Importa un CSV con datos GIATA en giata_properties_raw';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("El archivo no existe: $file");
            return 1;
        }

        $this->info("📥 Cargando CSV: $file");

        $handle = fopen($file, 'r');
        if (!$handle) {
            $this->error("No se pudo abrir el archivo CSV.");
            return 1;
        }

        $delimiter = ',';

        // 1) Leer cabecera cruda
        $rawHeader = fgets($handle);

        // 2) Eliminar BOM si existe
        $rawHeader = preg_replace('/^\xEF\xBB\xBF/', '', $rawHeader);

        // 3) Parsear cabecera ya sin BOM
        $header = str_getcsv($rawHeader, $delimiter);

        // 4) Normalizar cabecera (quitar comillas y espacios)
        $header = array_map(fn($h) => trim(trim($h, '"')), $header);

        // 5) Forzar SIEMPRE que la primera columna sea EXACTAMENTE "GIATA ID"
        $header[0] = 'GIATA ID';

        $this->info('Cabecera normalizada: ' . implode(' | ', $header));

        $count = 0;
        $line  = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $line++;

            if (count($row) !== count($header)) {
                continue;
            }

            $data = array_combine($header, $row);
            if (!$data) {
                continue;
            }

            // GIATA ID desde la cabecera corregida
            $giataId = trim($data['GIATA ID'], '" ');
            if (!is_numeric($giataId)) {
                continue;
            }

            $giataId = (int)$giataId;

            // lat / lon
            $lat = $data['Latitude'] !== '' ? (float)$data['Latitude'] : null;
            $lng = $data['Longitude'] !== '' ? (float)$data['Longitude'] : null;

            GiataPropertyRaw::updateOrCreate(
                ['giata_id' => $giataId],
                [
                    'name'             => $data['Name'] ?? null,
                    'rating'           => $data['Rating'] ?? null,
                    'city'             => $data['City'] ?? null,
                    'destination'      => $data['Destination'] ?? null,
                    'country_code'     => $data['Country code'] ?? null,
                    'address_lines'    => $data['Address lines'] ?? null,
                    'zipcode'          => $data['Postal code'] ?? null,
                    'phone'            => $data['Phone'] ?? null,
                    'fax'              => $data['Fax'] ?? null,
                    'email'            => $data['Email'] ?? null,
                    'website'          => $data['Url'] ?? null,
                    'latitude'         => $lat,
                    'longitude'        => $lng,
                    'accuracy'         => $data['Accuracy'] ?? null,
                    'last_change'      => $data['Last change'] ?? null,
                    'alternative_name' => $data['Alternative name'] ?? null,
                    'chain'            => $data['Chain'] ?? null,
                    'airport'          => $data['Airport'] ?? null,
                ]
            );

            $count++;

            if ($count <= 5) {
                $this->info("Fila $line → GIATA ID: $giataId, Name: ".$data['Name']);
            }

            if ($count % 1000 === 0) {
                $this->info("… procesadas {$count} filas");
            }
        }

        fclose($handle);

        $this->info("✔ Importación completada. Registros procesados: $count");
    }
}
