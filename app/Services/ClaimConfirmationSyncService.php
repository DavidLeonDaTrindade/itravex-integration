<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

class ClaimConfirmationSyncService
{
    public function sync(?string $connection = null): array
    {
        $connection = $connection ?: DB::getDefaultConnection();
        $db = DB::connection($connection);

        $claimNumber = (int) config('services.samo.claim_number', 1510263);
        $lastStamp = (int) ($db->table('claim_confirmations')->max('changestamp') ?? 0);

        $totalUpserted = 0;
        $iterations = 0;
        $startedFrom = $lastStamp;

        while (true) {
            $iterations++;

            $response = Http::timeout(60)->get(config('services.samo.base_url'), [
                'method' => 'get_claimconfirmation',
                'username' => config('services.samo.username'),
                'password' => config('services.samo.password'),
                'claim_id' => $claimNumber,
                'Last_ChangeStamp' => $lastStamp,
            ]);

            if ($response->failed()) {
                Log::error('Error al consultar claim confirmations en SAMO', [
                    'connection' => $connection,
                    'claim' => $claimNumber,
                    'last_changestamp' => $lastStamp,
                    'status' => $response->status(),
                    'response' => $response->body(),
                ]);

                throw new RuntimeException("SAMO devolvio un error HTTP {$response->status()}.");
            }

            try {
                $xml = new SimpleXMLElement($response->body());
            } catch (Throwable $exception) {
                Log::error('Respuesta XML invalida al sincronizar claim confirmations', [
                    'connection' => $connection,
                    'claim' => $claimNumber,
                    'last_changestamp' => $lastStamp,
                    'response' => $response->body(),
                    'exception' => $exception->getMessage(),
                ]);

                throw new RuntimeException('La respuesta de SAMO no es un XML valido.', 0, $exception);
            }

            $dataNode = $xml->response->data ?? null;

            if (! $dataNode) {
                break;
            }

            $raw = (string) $dataNode;
            $newStamp = (int) ($dataNode['new_changestamp'] ?? 0);

            if ($newStamp <= $lastStamp) {
                break;
            }

            preg_match_all('/\{CM\|([^}]+)\}/', $raw, $matches);

            $now = now();
            $batch = [];

            foreach ($matches[1] ?? [] as $entry) {
                $parts = explode('|', rtrim($entry, ';'));

                if (count($parts) < 4 || ! is_numeric($parts[0])) {
                    continue;
                }

                $claim = (int) $parts[0];

                $batch[$claim] = [
                    'claim' => $claim,
                    'changestamp' => $newStamp,
                    'status' => (string) ($parts[1] ?? ''),
                    'flag' => $this->normalizeString($parts[2] ?? null),
                    'comment' => $this->normalizeString($parts[3] ?? null),
                    'cost' => isset($parts[4]) && $parts[4] !== '' ? (float) $parts[4] : 0.0000,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            if ($batch !== []) {
                $rows = array_values($batch);

                $db->table('claim_confirmations')->upsert(
                    $rows,
                    ['claim'],
                    ['changestamp', 'status', 'flag', 'comment', 'cost', 'updated_at']
                );

                $totalUpserted += count($rows);
            }

            $lastStamp = $newStamp;
        }

        return [
            'connection' => $connection,
            'claim_number' => $claimNumber,
            'started_from' => $startedFrom,
            'last_changestamp' => $lastStamp,
            'rows_upserted' => $totalUpserted,
            'iterations' => $iterations,
        ];
    }

    private function normalizeString(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
