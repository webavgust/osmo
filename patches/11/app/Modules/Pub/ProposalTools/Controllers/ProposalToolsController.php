<?php

namespace App\Modules\Pub\ProposalTools\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalTools\Services\ProposalPriceHistoryService;
use App\Modules\Pub\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ProposalToolsController extends Controller
{
    /**
     * История изменения цен по итерациям КП
     *
     * @param Request $request
     * @param Proposal $proposal
     * @return \Illuminate\Contracts\View\View
     */
    public function price_history(Request $request, Proposal $proposal)
    {
        $rows = ProposalPriceHistoryService::iterations($proposal->group);
        if ($rows->isEmpty()) abort(404);

        // по умолчанию сравниваем предпоследнюю итерацию с последней
        $numbers = $rows->pluck('iteration');
        $to = (int) $request->input('to', $numbers->last());
        $from = (int) $request->input('from', $numbers->count() > 1 ? $numbers[$numbers->count() - 2] : $numbers->first());

        $from_proposal = ProposalRepository::getOnce($proposal->group, $from);
        $to_proposal = ProposalRepository::getOnce($proposal->group, $to);

        $diff = $from_proposal && $to_proposal && $from !== $to
            ? ProposalPriceHistoryService::diff($from_proposal, $to_proposal)
            : collect();

        return View::make('pub.proposal_tools.price_history', [
            'title' => 'История цен: ' . $proposal->name,
            'proposal' => $rows->last()['proposal'],
            'rows' => $rows,
            'blocks' => ProposalPriceHistoryService::blocks(),
            'from' => $from,
            'to' => $to,
            'diff' => $diff,
            'diff_total' => ProposalPriceHistoryService::diffTotal($diff),
        ]);
    }

    /**
     * Попап клонирования КП
     *
     * @param Proposal $proposal
     * @param int|null $iteration
     * @return \Illuminate\Contracts\View\View
     */
    public function box_clone(Proposal $proposal, int $iteration = null)
    {
        $proposal = $iteration
            ? ProposalRepository::getOnce($proposal->group, $iteration)
            : $proposal;

        if (empty($proposal)) abort(404);
        $max_number = Proposal::max('number_int') + 1;

        return View::make('pub.proposal_tools.boxes.clone', [
            'title' => 'Клонирование КП',
            'proposal' => $proposal,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'partners' => Partner::orderBy('name')->get(['id', 'name']),
            'managers' => User::orderBy('name')->get(['id', 'name']),
            'max_number' => $max_number,
        ]);
    }
}
