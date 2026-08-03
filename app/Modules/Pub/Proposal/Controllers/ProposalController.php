<?php

namespace App\Modules\Pub\Proposal\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Repositories\NeuroserviceRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\User\Models\User;
use App\Http\Controllers\Controller;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

class ProposalController extends Controller
{
    use HasBreadcrumb;

    public $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new ProposalRepository();
        $this->service = new ProposalService();
        $this->breadcrumb_add(route('proposal.index'), 'КП');
    }

    /**
     * Страница со списком
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $filter_service = new ProposalListFilterService();
        $table_data = $this->repo->getTable();

        $partners = PartnerRepository::getAll();
        $companies = CompanyRepository::getAll();
        $scenarios = ScenarioRepository::getAll();
        $neuroservices = NeuroserviceRepository::getAll();

        $managers = UserRepository::getProposalAuthors();

        return view('pub.proposal.index', [

            'breadcrumbs' => $this->breadcrumb,
            'partners' => $partners,
            'companies' => $companies,
            'scenarios' => $scenarios,
            'neuroservices' => $neuroservices,
            'filter' => $filter_service->getFilter(),
            'filter_count' => $filter_service->getFilterCount(),
            'managers' => $managers,
            'user' => []
        ]);
    }


    public function create(Request $request)
    {
        $this->breadcrumb_add(null, 'Создание');

        $companies = CompanyRepository::getAll();
        $partners = PartnerRepository::getAll();
        $scenarios = ScenarioRepository::getActive();
        $users = UserRepository::getAll();
        $max_number = Proposal::max('number_int') + 1;
        $works = WorkRepository::getAll();
        $softs = SoftwareRepository::getAll();
        $company_default = $request->get('company') ?? null;
        $nds = Constant::get('nds_rate');
        $costs = ScenarioRepository::getCosts();
        $cost_rules = ScenarioRepository::getCostRules();

        // добавим платформу
        $cost_rules[0] = json_decode(Constant::get('platform_cost_per_year'), 1);

        return view('pub.proposal.create', [
            'breadcrumbs' => $this->breadcrumb,
            'scenarios' => $scenarios,
            'companies' => $companies,
            'partners' => $partners,
            'costs' => $costs,
            'cost_rules' => $cost_rules,
            'users' => $users,
            'works' => $works,
            'softs' => $softs,
            'nds' => $nds,
            'max_number' => $max_number,
            'currencies' => CurrencyRepository::getAll(),
            'company_default' => $company_default,
        ]);
    }

    public function detail(Proposal $proposal, int $iteration = 1)
    {

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $this->breadcrumb_add(route('proposal.detail', [$proposal, $proposal->iteration]), $proposal->number . " ({$proposal->iteration})");
        $iterations = ProposalRepository::getIterations($proposal->group);

        return view('pub.proposal.detail', [
            'breadcrumbs' => $this->breadcrumb,
            'proposal' => $proposal,
            'iterations' => $iterations,
        ]);
    }


    /**
     * Форма редактирования
     *
     * @param \App\Modules\Pub\Proposal\Models\Proposal $Proposal
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Proposal $proposal, int $iteration = 1)
    {
        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $this->breadcrumb_add(route('proposal.detail', [$proposal, $proposal->iteration]), $proposal->number . " ({$proposal->iteration})");
        $this->breadcrumb_add('', 'Редактирование');


        $companies = CompanyRepository::getAll();
        $partners = PartnerRepository::getAll();


        $scenarios_have = $proposal->variants->flatMap->proposal_scenarios->unique('scenario_id');
        $platforms_have = $proposal->variants->flatMap->proposal_platforms->unique('id');
        $scenarios = ScenarioRepository::getActive($scenarios_have->pluck('scenario_id')->toArray());

        $costs = ScenarioRepository::getCosts($proposal->currency_rate_cumulative);




        // получим матрицу сценариев

        $matrix = ProposalService::getMatrix($proposal);
        $scenario_matrix = $matrix['scenario_matrix'];
        $platform_matrix = $matrix['platform_matrix'];

        $users = UserRepository::getAll();
        $max_number = Proposal::max('number_int') + 1;

        $works = WorkRepository::getAll();
        $softs = SoftwareRepository::getAll();

        $proposal_variants = $proposal->variants;
        $work_groups = $proposal->works->pluck('group')->filter(function($group) {
            return !empty($group);
        })->unique();


        $costs = ScenarioRepository::getCosts();
        $cost_rules = ScenarioRepository::getCostRules($proposal->currency_rate_cumulative);


        $neuroForceCost = ProposalService::getNeuroForceCost($proposal);

        return view('pub.proposal.edit', [
            'breadcrumbs' => $this->breadcrumb,
            'proposal' => $proposal,
            'scenarios' => $scenarios,
            'companies' => $companies,
            'partners' => $partners,
            'scenarios_have' => $scenarios_have,
            'scenario_matrix' => $scenario_matrix,
            'platforms_have' => $platforms_have,
            'platform_matrix' => $platform_matrix,
            'costs' => collect($costs),
            'users' => $users,
            'works' => $works,
            'work_groups' => $work_groups,
            'softs' => $softs,
            'max_number' => $max_number,
            'proposal_variants' => $proposal_variants,
            'cur_symbol' => $proposal->currency->symbol,
            'currency_koef' => 1 / $proposal->currency_rate_cumulative,
            'cost_rules' => $cost_rules,
            'neuroForceCost' => $neuroForceCost,
        ]);
    }

    /**
     * Обновление
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Modules\Pub\Proposal\Models\Proposal $Proposal
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ProposalUpdateRequest $request, Proposal $Proposal)
    {
        //
        $Proposal->user()->associate($request->input('user'));
        $Proposal->course()->associate($request->input('course'));
        $Proposal->fill($request->only($Proposal->getFillable()))->save();

        return \Redirect::route('Proposals.index');
    }

    /**
     * Удаление
     *
     * @param \App\Modules\Pub\Proposal\Models\Proposal $Proposal
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Proposal $Proposal)
    {
        $Proposal->delete();

        return \Redirect::route('Proposals.index');
    }


    // BOXES
    public function box_generate_pdf(Proposal $proposal, int $iteration = 1)
    {

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $template = View::make('pub.proposal.boxes.generate_pdf', [
            'title' => 'Создание PDF',
            'proposal' => $proposal,
        ]);

        return $template;
    }

    public function box_convert(Proposal $proposal, int $iteration = 1)
    {

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $currencies = CurrencyRepository::getForeign();

        $template = View::make('pub.proposal.boxes.convert', [
            'title' => 'Конвертирование PDF',
            'proposal' => $proposal,
            'currencies' => $currencies,
        ]);

        return $template;
    }


    // SIDEBARS
    public function sidebar_iterations(Proposal $proposal)
    {
        $iterations = ProposalRepository::getIterations($proposal->group);
        $template = View::make('pub.proposal.create.sidebars.iterations', [
            'title' => 'Просмотр редакций КП',
            'iterations' => $iterations,
            'proposal' => $proposal,
        ]);

        return $template;
    }

    public function report_get(Request $request, Proposal $proposal, int $iteration = 1)
    {
        return Redirect::route('proposal.detail', [$proposal, $iteration]);
    }
    public function report(Request $request, Proposal $proposal, int $iteration = 1)
    {
        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $data = $request->all();

        $variants = $proposal->variants->whereIn('id', $data['active']);


        app()->setLocale($data['language'] ?? 'ru');

        if('ru' == ($data['language'] ?? 'ru')) {
            $title = 'Osmoview_ТКП_' . $proposal->partner->name . '_' . $proposal->company->name . '_' . $proposal->number .'_' . $proposal->sended_at->format("Y-m-d");
        } else {
            $title = 'Osmoview_TCP_' . $proposal->partner->name . '_' . $proposal->company->name . '_' . $proposal->number .'_' . $proposal->sended_at->format("Y-m-d");
        }

        $template = match($request->input('template') ?? null) {
            "client_discount" => "pub.proposal.generate.pdf_client_discount",
            default => "pub.proposal.generate.pdf"
        };


        return view($template, [
            'template' => $template,
            'title_force' => $title,
            'proposal' => $proposal,
            'variants' => $variants,
            'data' => $data,
        ]);
    }
}
