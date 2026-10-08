<?php

namespace App\Modules\Bitrix\CrmDeal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalCrmDeal;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Illuminate\Http\Request;

/**
 * AJAX реестра сделок (patch v24).
 *
 * Привязка КП к сделке прямо из реестра — обратная сторона попапа
 * «Прикрепление сделки к Битрикс24» на карточке КП: там к КП подбирают
 * сделки, здесь к сделке подбирают КП. Правила общие и живут в
 * ProposalDealService: связь многие-ко-многим (patch v43) — у КП может быть
 * несколько сделок, к сделке можно привязать несколько КП; привязка ставится
 * на всю группу итераций.
 */
class ApiCrmDealController extends Controller
{
    /**
     * Поиск КП для привязки к сделке
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return array
     */
    public function proposalSearch(Request $request, CrmDeal $deal)
    {
        // партнёра берём из сделки сами: клиенту тут доверять нечего,
        // он только говорит, снимать область партнёра или нет
        $partner = $request->boolean('all_partners') ? null : DealProjectService::resolvePartner($deal);

        $rows = ProposalDealService::searchProposals([
            'q' => $request->input('q'),
            'partner_id' => $partner?->id,
            'deal_id' => (int) $deal->id,
        ]);

        return [
            'result' => 'success',
            'count' => $rows->count(),
            'partner' => $partner?->name,
            'rows' => $rows->map(fn(Proposal $proposal) => [
                'group' => $proposal->group,
                'number' => $proposal->number,
                'name' => $proposal->name,
                'partner' => $proposal->partner?->name,
                'company' => $proposal->company?->name,
                'manager' => $proposal->manager?->full_name,
                'date' => $proposal->sended_at?->format('d.m.Y'),
                'currency' => strtoupper((string) $proposal->currency_slug),
                'amount' => (float) $proposal->cost_total,
                'deals_count' => $proposal->deals_count,
                // patch v43: справочно — уже привязано к этой сделке и другие сделки КП
                'attached_here' => $proposal->attached_here,
                'deals' => $proposal->deals,
                'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
            ]),
        ];
    }

    /**
     * Привязать КП к сделке.
     * К сделке можно привязать несколько КП (patch v43) — запрета нет.
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return array {result, message, proposals: [{group, number, name, url, is_main}]} — все КП сделки после привязки
     */
    public function proposalAttach(Request $request, CrmDeal $deal)
    {
        $request->validate(['proposal_group' => 'required|string']);

        $proposal = ProposalDealService::lastProposal((string) $request->input('proposal_group'));
        if (empty($proposal)) {
            return ['result' => 'error', 'message' => 'КП не найдено'];
        }

        try {
            ProposalDealService::attach($proposal, (int) $deal->id);
        } catch (\InvalidArgumentException $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return [
            'result' => 'success',
            'message' => 'Сделка привязана к КП ' . ($proposal->number ?: $proposal->name),
            'proposals' => ProposalDealService::dealProposalRows((int) $deal->id),
        ];
    }

    /**
     * Отвязать от сделки одно КП (patch v43).
     *
     * КП указывается обязательным параметром proposal_group в теле запроса:
     * у сделки может быть несколько КП, отвязывается ровно указанное.
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return array {result, message, proposals: [{group, number, name, url, is_main}]} — оставшиеся КП сделки
     */
    public function proposalDetach(Request $request, CrmDeal $deal)
    {
        $request->validate(['proposal_group' => 'required|string']);

        $group = (string) $request->input('proposal_group');

        // связь должна существовать — иначе отвязывать нечего
        $linked = ProposalCrmDeal::where('proposal_group', $group)
            ->where('crm_deal_id', (int) $deal->id)
            ->exists();

        $proposal = $linked ? ProposalDealService::lastProposal($group) : null;
        if (empty($proposal)) {
            return ['result' => 'error', 'message' => 'Это КП к сделке не привязано'];
        }

        ProposalDealService::detach($proposal, (int) $deal->id);

        return [
            'result' => 'success',
            'message' => 'Сделка отвязана от КП ' . ($proposal->number ?: $proposal->name),
            'proposals' => ProposalDealService::dealProposalRows((int) $deal->id),
        ];
    }
}
