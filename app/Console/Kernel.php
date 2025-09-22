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

    ];

    protected function schedule(Schedule $schedule): void
    {
        // Puedes programar tareas aquí si lo deseas
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
