<?php

namespace App\Modules\Bitrix\Dashboard\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Bitrix\Dashboard\Services\DashboardFilterService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Illuminate\Http\Request;
use App\Modules\Bitrix\Dashboard\Repositories\DashboardRepository;
use App\Modules\Bitrix\Dashboard\Services\DashboardService;
use Illuminate\Support\Facades\Cache;


class DashboardController extends Controller
{
    use HasBreadcrumb;

    public function __construct(
        protected $repo = new DashboardRepository(),
        protected $service = new DashboardService(),
    ) {
        $this->breadcrumb_add(null, 'Воронка продаж');
    }

    public function index()
    {
        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currency = CurrencyRepository::get($currency_slug);
        $filter = DashboardFilterService::getFilter();
        $deals_issues = CrmDealRepository::getDealWithIssues();

        return view('bitrix.dashboard.index', [
            'breadcrumbs' => $this->breadcrumb,
            'currency' => $currency,
            'filter' => $filter,
                'deals_issues' => $deals_issues,
        ]);
    }
}
