<?php

namespace App\Modules\Bitrix\CrmDeal\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Illuminate\Http\Request;

/**
 * AJAX реестра сделок (patch v24).
 *
 * Привязка КП к сделке прямо из реестра — обратная сторона попапа
 * «Прикрепление сделки к Битрикс24» на карточке КП: там к КП подбирают
 * сделки, здесь к сделке подбирают КП. Правила общие и живут в
 * ProposalDealService: одна сделка принадлежит одному КП, у КП сделок
 * может быть несколько, привязка ставится на всю группу итераций.
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
                'url' => route('proposal.detail', [$proposal, $proposal->iteration]),
            ]),
        ];
    }

    /**
     * Привязать КП к сделке
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return array
     */
    public function proposalAttach(Request $request, CrmDeal $deal)
    {
        $request->validate(['proposal_group' => 'required|string']);

        $proposal = ProposalDealService::lastProposal((string) $request->input('proposal_group'));
        if (empty($proposal)) {
            return ['result' => 'error', 'message' => 'КП не найдено'];
        }

        // сделка принадлежит одному КП: подсказываем кнопку «Отвязать» этого же попапа
        $current = ProposalDealService::proposalOfDeal((int) $deal->id);
        if ($current && $current->group !== $proposal->group) {
            return [
                'result' => 'error',
                'message' => 'Сделка уже привязана к КП ' . ($current->number ?: $current->name)
                    . ' — сначала отвяжите её кнопкой «Отвязать» выше.',
            ];
        }

        try {
            ProposalDealService::attach($proposal, (int) $deal->id);
        } catch (\InvalidArgumentException $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return [
            'result' => 'success',
            'message' => 'Сделка привязана к КП ' . ($proposal->number ?: $proposal->name),
        ];
    }

    /**
     * Отвязать сделку от её КП
     *
     * @param Request $request
     * @param CrmDeal $deal
     * @return array
     */
    public function proposalDetach(Request $request, CrmDeal $deal)
    {
        $proposal = ProposalDealService::proposalOfDeal((int) $deal->id);
        if (empty($proposal)) {
            return ['result' => 'error', 'message' => 'К сделке не привязано КП'];
        }

        ProposalDealService::detach($proposal, (int) $deal->id);

        return [
            'result' => 'success',
            'message' => 'Сделка отвязана от КП ' . ($proposal->number ?: $proposal->name),
        ];
    }
}
