<?php

namespace App\Modules\Pub\Dashboard\Services;

use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Illuminate\Support\Facades\DB;

class DashboardDataService
{
    public function __construct()
    {
        // получим срез конвертированных данных
        $deals = DB::connection('bitrix')->table('crm_deal')->get();
//        $rates = CurrencyRepository::getRates(['date' => now(), 'returnFull' => true]);

        $deals->map(function ($item) {
            $item->account_currency_id = 'asdsad';
        });

        dd($deals->first());
    }


    public static function sales()
    {

    }
}
