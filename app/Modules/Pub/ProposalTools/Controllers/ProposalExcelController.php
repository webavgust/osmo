<?php

namespace App\Modules\Pub\ProposalTools\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalTools\Services\ProposalExcelService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Выгрузка КП в Excel (patch v16).
 *
 * Устроено как создание PDF: попап с выбором вариантов и шаблона, отправка
 * формы отдаёт файл. Шаблонов два — внутренний и для заказчика.
 */
class ProposalExcelController extends Controller
{
    /**
     * Попап выгрузки
     *
     * @param Proposal $proposal
     * @param int|null $iteration
     * @return \Illuminate\Contracts\View\View
     */
    public function box(Proposal $proposal, int $iteration = null)
    {
        $proposal = $iteration
            ? ProposalRepository::getOnce($proposal->group, $iteration)
            : $proposal;

        if (empty($proposal)) abort(404);

        return View::make('pub.proposal_tools.boxes.excel', [
            'title' => 'Создание Excel',
            'proposal' => $proposal,
            'templates' => ProposalExcelService::TEMPLATES,
        ]);
    }

    /**
     * Файл
     *
     * @param Request $request
     * @param Proposal $proposal
     * @param int|null $iteration
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function download(Request $request, Proposal $proposal, int $iteration = null)
    {
        $request->validate([
            'active' => 'nullable|array',
            'template' => 'nullable|string|in:' . implode(',', array_keys(ProposalExcelService::TEMPLATES)),
            'show_unprocessed' => 'nullable|boolean',
        ]);

        $proposal = $iteration
            ? ProposalRepository::getOnce($proposal->group, $iteration)
            : $proposal;

        if (empty($proposal)) abort(404);

        return ProposalExcelService::download(
            proposal: $proposal,
            variant_ids: array_map('intval', (array) $request->input('active', [])),
            template: (string) $request->input('template', 'default'),
            show_unprocessed: $request->boolean('show_unprocessed')
        );
    }
}
