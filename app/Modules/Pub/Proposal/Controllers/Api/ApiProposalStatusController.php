<?php

namespace App\Modules\Pub\Proposal\Controllers\Api;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalLostReason;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use Illuminate\Http\Request;

/**
 * Статус КП и привязка к сделке Битрикса.
 *
 * Вынесено в отдельный контроллер, чтобы не раздувать ApiProposalController.
 */
class ApiProposalStatusController
{
    /**
     * Сменить статус
     *
     * @param Request $request
     * @param Proposal $proposal
     * @param int $iteration
     * @return array
     */
    public function status(Request $request, Proposal $proposal, int $iteration)
    {
        $request->validate([
            'status' => 'required|string',
            'reason' => 'nullable|string',
            'comment' => 'nullable|string|max:500',
        ]);

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if (empty($proposal)) abort(404);

        $status = ProposalStatus::tryFrom($request->input('status'));
        if (empty($status)) {
            return ['result' => 'error', 'message' => 'Неизвестный статус'];
        }

        $reason = ProposalLostReason::tryFrom((string) $request->input('reason'));

        try {
            ProposalStatusService::set(
                proposal: $proposal,
                status: $status,
                reason: $reason,
                comment: $request->input('comment')
            );
        } catch (\InvalidArgumentException $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return [
            'result' => 'success',
            'status' => $status->value,
            'label' => $status->data()['label'],
            'color' => $status->data()['color'],
        ];
    }

    /**
     * Поиск сделок (для живого поиска в попапе)
     *
     * @param Request $request
     * @param Proposal $proposal
     * @return array
     */
    public function dealSearch(Request $request, Proposal $proposal)
    {
        $deals = ProposalDealService::search([
            'q' => $request->input('q'),
            'manager' => $request->input('manager'),
            'stage' => $request->input('stage'),
            'company' => $request->input('company'),
            'only_free' => $request->boolean('only_free', true),
            'proposal_group' => $proposal->group,
        ]);

        return [
            'result' => 'success',
            'count' => $deals->count(),
            'rows' => $deals->map(fn($deal) => [
                'id' => $deal->id,
                'title' => $deal->title,
                'company' => $deal->company_name,
                'customer' => $deal->customer_name,
                'manager' => $deal->manager,
                'stage' => $deal->stage_name,
                'amount' => $deal->opportunity,
                'currency' => $deal->currency_id,
                'quarter' => $deal->plan_quarter,
                'is_taken' => $deal->is_taken,
                'taken_by' => $deal->taken_by?->name,
            ]),
        ];
    }

    /**
     * Привязать сделку
     *
     * @param Request $request
     * @param Proposal $proposal
     * @param int $iteration
     * @return array
     */
    public function dealAttach(Request $request, Proposal $proposal, int $iteration)
    {
        $request->validate(['deal_id' => 'required|integer']);

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if (empty($proposal)) abort(404);

        try {
            ProposalDealService::attach($proposal, (int) $request->input('deal_id'));
        } catch (\InvalidArgumentException $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return ['result' => 'success'];
    }

    /**
     * Отвязать сделку
     *
     * @param Proposal $proposal
     * @param int $iteration
     * @return array
     */
    public function dealDetach(Proposal $proposal, int $iteration)
    {
        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if (empty($proposal)) abort(404);

        ProposalDealService::detach($proposal);

        return ['result' => 'success'];
    }
}
