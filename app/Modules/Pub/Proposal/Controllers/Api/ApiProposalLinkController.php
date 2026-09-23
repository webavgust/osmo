<?php

namespace App\Modules\Pub\Proposal\Controllers\Api;

use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalLink;
use App\Modules\Pub\Proposal\Services\ProposalLinkService;
use Illuminate\Http\Request;

/**
 * Связка КП «главное / второстепенное» (patch v33): поиск пары, связать,
 * разъединить, поменять роли. Правила связки — в ProposalLinkService,
 * их нарушение (\DomainException) отдаётся как result = error с текстом.
 *
 * {proposal} — группа КП, работаем с последней редакцией.
 */
class ApiProposalLinkController
{
    /**
     * Поиск КП для связывания (живой поиск в попапе): готовые строки таблицы
     *
     * @param Request $request
     * @param Proposal $proposal
     * @return array
     */
    public function search(Request $request, Proposal $proposal)
    {
        $proposal = ProposalLinkService::last($proposal) ?? $proposal;
        $rows = ProposalLinkService::candidates($proposal, (string) $request->input('q', ''));

        return [
            'result' => 'success',
            'count' => $rows->count(),
            'html' => view('pub.proposal.boxes.link_rows', [
                'proposal' => $proposal,
                'rows' => $rows,
                'blockers' => ProposalLinkService::blockers($proposal),
            ])->render(),
        ];
    }

    /**
     * Связать КП с другим.
     * role = main — текущее КП становится главным, other — второстепенным;
     * role = secondary — наоборот.
     *
     * @param Request $request
     * @param Proposal $proposal
     * @return array
     */
    public function attach(Request $request, Proposal $proposal)
    {
        $request->validate([
            'other' => 'required|string|exists:proposals,group',
            'role' => 'required|in:main,secondary',
            'comment' => 'nullable|string|max:500',
        ]);

        $proposal = ProposalLinkService::last($proposal) ?? $proposal;
        $other = ProposalLinkService::last((string) $request->input('other'));
        if (empty($other)) abort(404);

        $is_main = $request->input('role') === 'main';

        try {
            ProposalLinkService::link(
                main: $is_main ? $proposal : $other,
                secondary: $is_main ? $other : $proposal,
                comment: $request->input('comment')
            );
        } catch (\DomainException $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return [
            'result' => 'success',
            'message' => $is_main
                ? 'КП ' . ProposalLink::refOf($other) . ' стало второстепенным'
                : 'КП стало второстепенным к ' . ProposalLink::refOf($other),
        ];
    }

    /**
     * Разъединить: secondary (по умолчанию само КП) снова участвует в расчётах
     *
     * @param Request $request
     * @param Proposal $proposal
     * @return array
     */
    public function detach(Request $request, Proposal $proposal)
    {
        $request->validate(['secondary' => 'nullable|string']);

        $secondary = $this->secondaryOf($request, $proposal);
        if (is_array($secondary)) return $secondary;

        try {
            ProposalLinkService::unlink($secondary);
        } catch (\DomainException $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return ['result' => 'success', 'message' => 'КП ' . ProposalLink::refOf($secondary) . ' разъединено с главным'];
    }

    /**
     * Сделать второстепенное главным: secondary (по умолчанию само КП)
     * меняется ролями со своим главным
     *
     * @param Request $request
     * @param Proposal $proposal
     * @return array
     */
    public function makeMain(Request $request, Proposal $proposal)
    {
        $request->validate(['secondary' => 'nullable|string']);

        $secondary = $this->secondaryOf($request, $proposal);
        if (is_array($secondary)) return $secondary;

        try {
            ProposalLinkService::makeMain($secondary);
        } catch (\DomainException $e) {
            return ['result' => 'error', 'message' => $e->getMessage()];
        }

        return ['result' => 'success', 'message' => 'КП ' . ProposalLink::refOf($secondary) . ' стало главным'];
    }

    /**
     * Второстепенное из запроса: само КП или одно из его второстепенных.
     * Чужие КП не трогаем — иначе попапом одного КП можно было бы
     * разъединять любые связки.
     *
     * @param Request $request
     * @param Proposal $proposal
     * @return Proposal|array КП или готовый ответ с ошибкой
     */
    protected function secondaryOf(Request $request, Proposal $proposal): Proposal|array
    {
        $group = trim((string) $request->input('secondary', ''));
        if ($group === '') $group = (string) $proposal->group;

        $own = $group === (string) $proposal->group
            || ProposalLink::where('main_group', $proposal->group)->where('secondary_group', $group)->exists();

        if (!$own) {
            return ['result' => 'error', 'message' => 'Это КП не связано с текущим'];
        }

        $secondary = ProposalLinkService::last($group);
        if (empty($secondary)) abort(404);

        return $secondary;
    }
}
