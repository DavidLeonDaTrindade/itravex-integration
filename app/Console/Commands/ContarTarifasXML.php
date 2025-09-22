<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ContarTarifasXML extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:contar-tarifas-x-m-l';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = storage_path('app/Rqoriginal.xml'); // Cambia si tu archivo está en otro lado

        if (!file_exists($filePath)) {
            $this->error("El archivo XML no se encontró en: {$filePath}");
            return;
        }

        $reader = new \XMLReader();
        $reader->open($filePath);

        $internalCount = 0;
        $externalCount = 0;

        while ($reader->read()) {
            if ($reader->nodeType === \XMLReader::ELEMENT && $reader->name === 'infhab') {
                $dom = new \DOMDocument();
                $node = $reader->expand();
                $domNode = $dom->importNode($node, true);
                $dom->appendChild($domNode);

                $hasInftrf = $dom->getElementsByTagName('inftrf')->length > 0;
                $hasCodtrf = $dom->getElementsByTagName('codtrf')->length > 0;

                if ($hasInftrf || $hasCodtrf) {
                    $internalCount++;
                } else {
                    $externalCount++;
                }
            }
        }

        $reader->close();

        $this->info("✅ Tarifas internas: {$internalCount}");
        $this->info("✅ Tarifas externas: {$externalCount}");
    }
}
