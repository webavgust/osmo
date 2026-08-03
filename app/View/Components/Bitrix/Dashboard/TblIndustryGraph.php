<?php

namespace App\View\Components\Bitrix\Dashboard;

use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\Component;

class TblIndustryGraph extends Component
{

    public function render(): View
    {
        $service = new DashboardDataService();
        $currency_target = Cache::get('dashboard_currency') ?? "RUB";
        $currency = CurrencyRepository::get($currency_target);

        $data = $service->industry_name();
        $graph = collect();


        foreach($data['matrix'] as $row => $columns) {
              $graph[$row] = $columns->flatMap->deals->sum('opportunity_RUB');
        }
        $graph = $graph->sortDesc();

        return view('components.bitrix.dashboard.tbl_industry_graph', [
            'graph' => $graph,
        ]);
    }
}
