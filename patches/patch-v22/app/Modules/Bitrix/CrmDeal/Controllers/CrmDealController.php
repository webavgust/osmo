<?php

namespace App\Modules\Bitrix\CrmDeal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealExportService;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use Illuminate\Http\Request;

/**
 * Реестр сделок Битрикса (patch v22).
 *
 * Страница показывает сделки с 2025 года и по умолчанию только те, к которым
 * ещё не привязано КП: именно они и есть работа — остальные уже посчитаны.
 * Фильтр живёт в адресе страницы, поэтому отбор можно отправить ссылкой.
 */
class CrmDealController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(null, 'Реестр сделок');
    }

    /**
     * Страница реестра
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $params = CrmDealRegistryService::params($request);
        $rows = CrmDealRegistryService::rows($params);

        return view('bitrix.deal.index', array_merge(CrmDealRegistryService::options(), [
            'title' => 'Реестр сделок Bitrix',
            'breadcrumbs' => $this->breadcrumb,
            'params' => $params,
            'rows' => $rows,
            'action' => route('crm-deal.index'),
            'partner' => null,
        ]));
    }

    /**
     * Выгрузка реестра в Excel: тот же фильтр, выбранные колонки
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $request->validate([
            'columns' => 'nullable|array',
            'columns.*' => 'string',
        ]);

        $params = CrmDealRegistryService::params($request);
        $rows = CrmDealRegistryService::rows($params);

        return CrmDealExportService::download($rows, (array) $request->input('columns', []));
    }
}
