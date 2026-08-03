<?php

namespace App\Console;

use App\Jobs\Portal\Orders\SyncAll;
use App\Modules\Pub\LabObject\Services\LabOjectService;
use App\Modules\Pub\Visit\Jobs\VisitCheckExpiredJob;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');


        require base_path('routes/console.php');
    }

    protected function schedule(Schedule $schedule)
    {
        $schedule->command('currency:update')->everyMinute();
//        $schedule->command('currency:update')->daily()->at('09:00');
    }

}
