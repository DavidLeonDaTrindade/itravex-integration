<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SyncGiataProvidersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 3600; // 1 hora (ajusta si quieres)
    public int $tries = 1;

    public function __construct(
        public array $providers,        // --provider=
        public array $providersWanted,  // --providers=
        public bool $saveCodes = true,
        public bool $onlyActive = true,
        public int $sleepMs = 100,
    ) {}

    public function handle(): void
    {
        Log::info('[GIATA JOB] Starting sync', [
            'providers' => $this->providers,
            'providersWanted' => $this->providersWanted,
        ]);

        Artisan::call('giata:sync-properties', [
            '--provider'    => implode(',', $this->providers),
            '--save-codes'  => $this->saveCodes,
            '--providers'   => implode(',', $this->providersWanted),
            '--only-active' => $this->onlyActive,
            '--sleep'       => $this->sleepMs,
        ]);

        Log::info('[GIATA JOB] Finished sync', [
            'exit_code' => Artisan::output() ? null : null, // no usamos exit aquí
        ]);
    }
}
