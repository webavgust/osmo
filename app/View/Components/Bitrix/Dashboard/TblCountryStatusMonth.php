<?php

namespace App\View\Components\Bitrix\Dashboard;

use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class TblCountryStatusMonth extends Component
{
    public function render(): View
    {
        $service = new DashboardDataService();
        $currency_target = Cache::get('dashboard_currency') ?? "RUB";
        $currency = CurrencyRepository::get($currency_target);

        $data = $service->country_status_month();

        return view('components.bitrix.dashboard.tbl_country_status__month', [
            'currency' => $currency,
            'data' => $data,
        ]);
    }
}
