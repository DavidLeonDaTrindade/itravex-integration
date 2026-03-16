<?php

namespace App\Console\Commands;

use App\Services\ClaimConfirmationSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class UpdateClaimConfirmations extends Command
{
    protected $signature = 'claim:update-confirmations {--connection= : Conexion de base de datos a usar}';

    protected $description = 'Sincroniza claim confirmations desde SAMO usando el ultimo changestamp guardado en la BD';

    public function __construct(
        private readonly ClaimConfirmationSyncService $syncService
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $result = $this->syncService->sync($this->option('connection') ?: null);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Conexion: {$result['connection']}");
        $this->info("Claim base: {$result['claim_number']}");
        $this->info("Changestamp inicial: {$result['started_from']}");
        $this->info("Ultimo changestamp: {$result['last_changestamp']}");
        $this->info("Confirmaciones sincronizadas: {$result['rows_upserted']}");

        return self::SUCCESS;
    }
}
