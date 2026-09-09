<?php

namespace App\Modules\Bitrix\CrmDeal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealExportService;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Bitrix\Dashboard\Services\DashboardFilterService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use Illuminate\Http\Request;
use App\Modules\Bitrix\Dashboard\Repositories\DashboardRepository;
use App\Modules\Bitrix\Dashboard\Services\DashboardService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;


class CrmDealBoxController extends Controller
{
    public function issues()
    {
        $deals = CrmDealRepository::getDealWithIssues();
        if($deals->isEmpty()) abort(404);

        return View::make('bitrix.deals.box.issues_sort', [
            'title' => 'Проблемные сделки',
            'deals' => $deals,
        ]);
    }

    /**
     * Попап выгрузки реестра сделок в Excel (patch v22).
     *
     * Текущий фильтр приезжает в адресе попапа и уходит в форму скрытыми
     * полями — выгружается ровно то, что видно на странице.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function export(Request $request)
    {
        $params = CrmDealRegistryService::params($request);

        return View::make('bitrix.deal.box.export', [
            'title' => 'Выгрузка реестра в Excel',
            'params' => $params,
            'count' => CrmDealRegistryService::rows($params)->count(),
            'columns' => CrmDealExportService::COLUMNS,
        ]);
    }
}
