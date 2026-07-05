<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        \App\Console\Commands\DispatchSmartNotifications::class,
        \App\Console\Commands\DatabaseBackup::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        // Run notification dispatch daily at 02:00 (server timezone)
        $schedule->command('notifications:dispatch')->dailyAt('02:00');

        // Daily database backup (command name defined in DatabaseBackup command)
        $schedule->command('database:backup')->daily()->at('03:00');
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
