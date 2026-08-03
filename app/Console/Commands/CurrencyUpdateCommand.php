<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CurrencyUpdateCommand extends Command
{
    protected $signature = 'currency:update';

    protected $description = 'Получение актуального курса валюты';

    public function handle(): void
    {
        \App\Modules\Pub\Currency\Services\CurrencyService::updateRates();
    }
}
