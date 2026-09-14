<?php

namespace App\Modules\Bitrix\CrmDeal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealExportService;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
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
    /**
     * Попап привязки КП к сделке (patch v24).
     *
     * Обратная сторона попапа «Прикрепление сделки к Битрикс24» с карточки КП:
     * там к КП подбирают сделки, здесь к сделке подбирают КП. Поиск сразу
     * подставляет партнёра сделки — нужное КП обычно у него.
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return \Illuminate\Contracts\View\View
     */
    public function proposal(Request $request, CrmDeal $deal)
    {
        // партнёр сделки известен по сопоставлению компании Битрикса (patch v23) —
        // тогда показываем только его КП, чужие в этой сделке всё равно не нужны
        $partner = DealProjectService::resolvePartner($deal);

        // партнёра нет — ищем словами: подставляем компанию сделки
        $q = $partner ? '' : trim((string) ($deal->company_name ?: $deal->dealUf?->{CrmDealRegistryService::ufCustomer()}));

        return View::make('bitrix.deal.box.proposal', [
            'title' => 'Привязка КП к сделке #' . $deal->id,
            'deal' => $deal,
            'deal_url' => CrmDealRegistryService::url($deal->id),
            'proposal' => ProposalDealService::proposalOfDeal((int) $deal->id),
            'partner' => $partner,
            'q' => $q,
            'rows' => ProposalDealService::searchProposals([
                'q' => $q,
                'partner_id' => $partner?->id,
            ]),
        ]);
    }

    public function export(Request $request)
    {
        // выгрузка уважает и фильтр, и вкладку реестра (patch v24)
        $mode = CrmDealRegistryService::mode($request->input('mode'));
        $params = CrmDealRegistryService::params($request, CrmDealRegistryService::modeDefaults($mode));

        return View::make('bitrix.deal.box.export', [
            'title' => 'Выгрузка реестра в Excel',
            'params' => $params,
            'mode' => $mode,
            'count' => CrmDealRegistryService::rows($params, null, $mode)->count(),
            'columns' => CrmDealExportService::COLUMNS,
        ]);
    }
}
