<?php

namespace App\Modules\Pub\ExternalProposal\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\ExternalProposal\Mappers\OsmoviewCpMapper;
use App\Modules\Pub\ExternalProposal\Models\ExternalProposal;
use App\Modules\Pub\ExternalProposal\Services\ExternalProposalService;
use App\Modules\Pub\ExternalProposal\Services\OsmoviewCpClient;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\User\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * КП OSMOVIEW CP: страница и попапы (patch v21)
 */
class ExternalProposalController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('proposal.index'), 'КП');
        $this->breadcrumb_add(route('external_proposal.index'), 'Внешние КП');
    }

    /**
     * Список внешних КП
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        // отбор живёт в адресе страницы (переживает F5) и уезжает в data-url таблицы
        $params = ExternalProposalService::params($request);

        $all = ExternalProposal::source();

        return View::make('pub.external_proposal.index', array_merge([
            'title' => 'Внешние КП',
            'breadcrumbs' => $this->breadcrumb,
            'params' => $params,
            'totals' => [
                'count' => (clone $all)->count(),
                'transferred' => (clone $all)->transferred(true)->count(),
                'without_payload' => (clone $all)->whereNull('payload')->count(),
            ],
            'api_configured' => (new OsmoviewCpClient())->configured(),
            'api_url' => config('services.osmoview_cp.base_url'),
        ], ExternalProposalService::options()));
    }

    /**
     * Попап «Подробнее»: ключевые поля, сценарии, работы, сырой JSON
     *
     * @param ExternalProposal $external
     * @return \Illuminate\Contracts\View\View
     */
    public function box_detail(ExternalProposal $external)
    {
        $preview = $external->has_payload ? OsmoviewCpMapper::preview($external) : null;

        return View::make('pub.external_proposal.boxes.detail', [
            'title' => 'OSMOVIEW CP ' . ($external->external_number ?: $external->external_id),
            'external' => $external,
            'preview' => $preview,
        ]);
    }

    /**
     * Попап «Импорт JSON»
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function box_import()
    {
        return View::make('pub.external_proposal.boxes.import', [
            'title' => 'Импорт JSON из OSMOVIEW CP',
        ]);
    }

    /**
     * Попап подтверждения переноса: партнёр, компания, менеджер, номер,
     * сопоставление сценариев, работы, суммы по прайсу
     *
     * @param Request $request
     * @param ExternalProposal $external
     * @return \Illuminate\Contracts\View\View
     */
    public function box_transfer(Request $request, ExternalProposal $external)
    {
        if (!$external->has_payload) {
            return View::make('pub.external_proposal.boxes.transfer', [
                'title' => 'Перенос ' . ($external->external_number ?: $external->external_id),
                'external' => $external,
                'preview' => null,
            ]);
        }

        $preview = OsmoviewCpMapper::preview($external);

        return View::make('pub.external_proposal.boxes.transfer', [
            'title' => 'Перенос ' . ($external->external_number ?: $external->external_id) . ' в КП',
            'external' => $external,
            'preview' => $preview,
            'partners' => Partner::orderBy('name')->get(['id', 'name', 'active']),
            'companies' => Company::orderBy('name')->get(['id', 'name', 'partner_id', 'active']),
            'users' => UserRepository::getAll(),
            'scenarios' => OsmoviewCpMapper::catalog(),
            'force' => !empty($external->proposal_group),
        ]);
    }
}
