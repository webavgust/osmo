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
 *
 * Вкладки (patch v24) — это тот же реестр в разрезе проекта сделки:
 * `mode=all` — все сделки, `projects` — с действующим проектом,
 * `archive` — с архивным. Вкладка тоже живёт в адресе.
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
        $mode = CrmDealRegistryService::mode($request->input('mode'));

        // на вкладках проектов отбор «сделки без КП» не имеет смысла
        $defaults = CrmDealRegistryService::modeDefaults($mode);
        $params = CrmDealRegistryService::params($request, $defaults);

        return view('bitrix.deal.index', array_merge(CrmDealRegistryService::options(), [
            'title' => 'Реестр сделок Bitrix',
            'breadcrumbs' => $this->breadcrumb,
            'params' => $params,
            'defaults' => $defaults,
            'rows' => CrmDealRegistryService::rows($params, null, $mode),
            'action' => route('crm-deal.index'),
            'partner' => null,
            'mode' => $mode,
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

        $mode = CrmDealRegistryService::mode($request->input('mode'));
        $params = CrmDealRegistryService::params($request, CrmDealRegistryService::modeDefaults($mode));
        $rows = CrmDealRegistryService::rows($params, null, $mode);

        return CrmDealExportService::download($rows, (array) $request->input('columns', []));
    }
}
