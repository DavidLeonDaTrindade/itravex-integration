<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\ImportHotelsByZone;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ImportHotelsByZone::class,
        \App\Console\Commands\ContarTarifasXML::class,
        \App\Console\Commands\ImportZones2::class,
        \App\Console\Commands\ImportHotelsByZone2::class,
        \App\Console\Commands\GiataSyncPropertiesBasic::class,
        \App\Console\Commands\GiataSyncProperties::class,   // 👈 AÑADIDO (tu comando grande de 312 líneas)
        \App\Console\Commands\GiataSyncProperties::class,

    ];


    protected function schedule(Schedule $schedule): void
    {
        // 1) Sonda: escribe una marca cada minuto
        $schedule->call(function () {
            file_put_contents(storage_path('logs/schedule_probe.txt'), date('c') . PHP_EOL, FILE_APPEND);
        })->everyMinute();

        // 2) Tu comando (sigue igual)
        $schedule->command('giata:sync-providers')
            ->everyMinute()  // temporal para probar
            ->withoutOverlapping()
            ->sendOutputTo(storage_path('logs/giata_sync.log'));
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
