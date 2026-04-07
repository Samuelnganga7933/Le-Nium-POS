<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * All custom Artisan commands for Le Nium POS.
     */
    protected $commands = [
        Commands\GenerateDailyReport::class,
        Commands\CleanHeldTransactions::class,
        Commands\CheckSubscriptions::class,
        Commands\SendLowStockAlerts::class,
        Commands\ExpireLoyaltyPoints::class,
        Commands\SeedDemoData::class,
    ];

    /**
     * Schedule is now handled in routes/console.php (Laravel 12 style).
     * This kernel is kept for backward compatibility.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Scheduled tasks live in routes/console.php
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        require base_path('routes/console.php');
    }
}
