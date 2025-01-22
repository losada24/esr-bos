<?php

namespace App\Console;

use App\Console\Commands\ChangeOrdersToExecutionStatus;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\SendExpiredInstallationTeamDocumentsDaily;
use App\Console\Commands\SendExpiredInstallationTeamDocumentsLiabilityDaily;

class Kernel extends ConsoleKernel
{

    protected $commands = [
        SendExpiredInstallationTeamDocumentsDaily::class,
        SendExpiredInstallationTeamDocumentsLiabilityDaily::class,
        ChangeOrdersToExecutionStatus::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('app:send-expired-installation-team-documents-daily')->weekly()->sundays()->at('00:01');
        $schedule->command('app:send-expired-installation-team-documents-liability-daily')->weekly()->sundays()->at('00:05');
        $schedule->command('app:change-orders-to-execution-status')->daily()->at('00:10');
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
