<?php

namespace App\Modules\Pub\Proposal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalLostReason;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use App\Modules\Pub\Proposal\Services\ProposalLinkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ProposalBoxController extends Controller
{
    /**
     * Смена статуса КП
     *
     * @param Proposal $proposal
     * @param int|null $iteration
     * @return \Illuminate\Contracts\View\View
     */
    public function status(Proposal $proposal, int $iteration = null)
    {
        $proposal = $iteration
            ? ProposalRepository::getOnce($proposal->group, $iteration)
            : $proposal;

        if (empty($proposal)) abort(404);

        return View::make('pub.proposal.boxes.status', [
            'title' => 'Статус КП «' . $proposal->name . '»',
            'proposal' => $proposal,
            'statuses' => ProposalStatus::getDecorated(),
            'reasons' => ProposalLostReason::getDecorated(),
        ]);
    }

    /**
     * Поиск и привязка сделок Битрикса.
     * У КП может быть несколько сделок — попап показывает список привязок
     * и поиск для новых.
     *
     * @param Request $request
     * @param Proposal $proposal
     * @param int|null $iteration
     * @return \Illuminate\Contracts\View\View
     */
    public function deal(Request $request, Proposal $proposal, int $iteration = null)
    {
        $proposal = $iteration
            ? ProposalRepository::getOnce($proposal->group, $iteration)
            : $proposal;

        if (empty($proposal)) abort(404);

        // при первом открытии подставляем название компании — чаще всего
        // нужная сделка находится именно по нему
        $q = $request->input('q', '');
        $prefill = $request->boolean('prefill', true);

        if ($prefill && $q === '' && !empty($proposal->company)) {
            $q = $proposal->company->name;
        }

        $params = [
            'q' => $q,
            'manager' => $request->input('manager'),
            'stage' => $request->input('stage'),
            'only_free' => $request->boolean('only_free', true),
            'proposal_group' => $proposal->group,
        ];

        return View::make('pub.proposal.boxes.deal', [
            'title' => 'Сделки Битрикс24',
            'proposal' => $proposal,
            'links' => ProposalDealService::links($proposal),
            'deals' => ProposalDealService::search($params),
            'managers' => ProposalDealService::managers(),
            'stages' => ProposalDealService::stages(),
            'params' => $params,
        ]);
    }

    /**
     * Связка КП «главное / второстепенное» (patch v33).
     * Второстепенному показываем главное и кнопки смены ролей / разъединения,
     * главному и КП без связки — его второстепенные и поиск пары.
     *
     * @param Request $request
     * @param Proposal $proposal
     * @param int|null $iteration
     * @return \Illuminate\Contracts\View\View
     */
    public function link(Request $request, Proposal $proposal, int $iteration = null)
    {
        $proposal = $iteration
            ? ProposalRepository::getOnce($proposal->group, $iteration)
            : $proposal;

        if (empty($proposal)) abort(404);

        // связка живёт на группе — работаем с последней редакцией
        $proposal = ProposalLinkService::last($proposal) ?? $proposal;

        $q = trim((string) $request->input('q', ''));
        $main = ProposalLinkService::mainOf($proposal);

        return View::make('pub.proposal.boxes.link', [
            'title' => 'Связка КП',
            'proposal' => $proposal,
            'main' => $main,
            'secondaries' => ProposalLinkService::secondariesOf($proposal),
            // второстепенное ни с кем больше не связывается — поиск ему не нужен
            'candidates' => $main ? collect() : ProposalLinkService::candidates($proposal, $q),
            'q' => $q,
            'blockers' => ProposalLinkService::blockers($proposal),
            // главное станет второстепенным при смене ролей — заранее видно, что мешает
            'main_blockers' => $main ? ProposalLinkService::blockers($main) : [],
        ]);
    }
}
