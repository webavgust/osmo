<?php

namespace App\Modules\Pub\Log\Controllers\Api;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Log\Repositories\LogRepository;
use App\Modules\Pub\Log\Services\LogService;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Repositories\NeuroserviceRepository;
use App\Modules\Pub\Proposal\Controllers\ProposalUpdateRequest;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\User\Models\User;
use App\Http\Controllers\Controller;
use App\View\Components\Log\Story;
use App\View\Components\Proposal\LogTable;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class LogController extends Controller
{
    public $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new LogRepository();
        $this->service = new LogService();
    }

    public function create(Request $request)
    {
        $request->validate([
            'company' => 'required|exists:companies,id',
            'proposal_group' => 'nullable|exists:proposals,group',
            'text' => 'required|string',
            'date' => 'required|date',
        ]);

        $this->repo->create($request->all());

        return ['result' => 'success'];
    }




    public function store(Request $request)
    {
        $request->validate([
            'parent' => 'nullable|integer|exists:proposals,id',
            'name' => 'required|string',
            'date' => 'required|date',
            'company' => 'required|exists:companies,id',
            'partner' => 'required|exists:partners,id',
            'manager' => 'required|exists:partners,id',
            'cost_source' => 'nullable|string|in:save,current',
            'period_main' => 'required|int|min:1|max:1000',
            'period_active' => 'required|array',
            'period_active.*' => 'bool',
            'partner_discount' => 'nullable',
            'partner_discount.*' => 'nullable|int|min:0|max:100',
            'period' => 'required|array',
            'period.*' => 'string|in:pilot,year,unlimited',
            'period_value' => 'required|array',
            'period_value.*' => 'nullable|numeric',
            'scenario' => 'required|array',
            'scenario.*' => 'nullable|exists:scenarios,id',
            'cell' => 'required|array',
            'cell.*.*.count' => 'nullable|numeric|min:1',
            'cell.*.*.discount' => 'nullable|numeric|min:0|max:100',
        ]);

        ProposalRepository::create($request);

        return ['result' => 'success', 'url' => route('proposal.index')];
    }

    public function story(Request $request)
    {
        $request->validate([
            'date' => 'required|date'
        ]);

        $date = Carbon::createFromTimestamp(strtotime($request->input('date')));

        $component = new Story(date: $date);
        $html = $component->render()->render();

        return ["result" => "success", "html" => $html];
    }

    public function fast(Request $request)
    {
        $request->validate([
            'group' => 'required|exists:proposals,group',
            'text' => 'required|string',
        ]);

        $proposal = ProposalRepository::getByGroup($request->input('group'));


        $this->repo->create([
            'company' => $proposal->company->id,
            'proposal_group' => $proposal->group,
            'text' => $request->input('text'),
            'date' => now()
        ]);

        $component = new LogTable(proposal: $proposal);
        $html = $component->render()->render();

        return ["result" => "success", "html" => $html];
    }
}
