<?php

namespace App\Services;

use App\Http\Clients\ItravexClient;
use Illuminate\Support\Facades\Cache;

class ItravexService
{
    protected $client;

    public function __construct(ItravexClient $client)
    {
        $this->client = $client;
    }

    public function openSession(): ?string
    {
        $xml = $this->buildOpenSessionRequest();
        $response = $this->client->sendXml($xml); // Aquí haces el POST

        logger("Respuesta XML:", [$response]);

        $doc = simplexml_load_string($response);

        if ($doc && isset($doc->ideses)) {
            return (string) $doc->ideses;
        }

        return null;
    }




    protected function buildOpenSessionRequest(): string
    {
        $codsys = config('itravex.codsys');
        $codage = config('itravex.codage');
        $user = config('itravex.user');
        $pass = config('itravex.pass');

        return <<<XML
<SesionAbrirPeticion>
    <codsys>{$codsys}</codsys>
    <codage>{$codage}</codage>
    <idtusu>{$user}</idtusu>
    <pasusu>{$pass}</pasusu>
</SesionAbrirPeticion>
XML;
    }


    protected function parseSessionId(string $xml): ?string
    {
        $doc = simplexml_load_string($xml);
        return $doc->ideses ?? null;
    }

    public function sendRaw(string $xml): string
    {
        return $this->client->sendXml($xml);
    }
}
