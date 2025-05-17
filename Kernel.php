<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('backup:db')
            ->hourly()
            ->when(function () {
                $hourUtc = (int) now()->format('H'); // jam UTC

                // cek jam UTC antara 1 sampai 16
                return $hourUtc >= 1 && $hourUtc <= 16;
            });

        // Report harian setiap jam 10 malam jam jakarta
        $schedule->command('report:apotekdamar')
            ->dailyAt('22:00')
            ->timezone('Asia/Jakarta');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
