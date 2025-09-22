<?php

namespace App\Http\Clients;

use Illuminate\Support\Facades\Http;


class ItravexClient
{
    public function sendXml(string $xml): string
    {
        $client = new \GuzzleHttp\Client();
        $endpoint = config('itravex.endpoint');

        $response = $client->post($endpoint, [
            'headers' => [
                'Content-Type' => 'application/xml',
            ],
            'body' => $xml,
        ]);

        return (string) $response->getBody();
    }
}
