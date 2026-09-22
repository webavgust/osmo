<?php

namespace App\Console;

use App\Jobs\Portal\Orders\SyncAll;
use App\Modules\Pub\LabObject\Services\LabOjectService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Illuminate\Support\Facades\Log;

class Kernel extends ConsoleKernel
{
    /**
     * Команды модулей (patch v29): каталог app/Console/Commands загружается
     * автоматически, команды модулей регистрируются здесь
     *
     * @var array
     */
    protected $commands = [
        \App\Modules\Pub\EntityLog\Console\BaselineCommand::class,
        \App\Modules\Pub\EntityLog\Console\RediffCommand::class,
    ];

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
        // Курсы ЦБ РФ раз в день. Окно в неделю добирает дни, когда ЦБ или сеть не ответили.
        // currency:update (exchangerate-api) из расписания убран: запуск каждую минуту выбирал
        // месячный лимит бесплатного ключа за сутки, и курсы переставали обновляться
        $schedule->command('currency:fetch-rates', [
            '--from' => now()->subDays(7)->toDateString(),
            '--to' => now()->toDateString(),
        ])->dailyAt('09:00')->withoutOverlapping();
    }

}
