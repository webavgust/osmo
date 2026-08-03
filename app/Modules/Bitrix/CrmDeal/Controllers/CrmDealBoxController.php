<?php

namespace App\Modules\Bitrix\CrmDeal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
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
}
