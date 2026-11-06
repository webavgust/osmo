<?php

namespace App\Modules\Pub\Company\Controllers;

use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Bitrix\CrmDeal\Models\CrmDeal;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationProposal;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\DealProject\Models\DealProject;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\ContractSpecification\Repository\ContractSpecificationRepository;
use App\Modules\Pub\Country\Repositories\CountryRepository;
use App\Modules\Pub\EntityLog\Services\EntityLogViewService;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Services\EducationApplicationListFilterService;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Models\CompanyGrade;
use App\Modules\Pub\Company\Models\CompanyType;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Company\Services\CompanyListFilterService;
use App\Modules\Pub\Company\Services\CompanyService;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Company\Requests\CompanyUpdateRequest;
use App\Modules\Pub\User\Repositories\UserRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class CompanyController extends Controller
{
    use HasBreadcrumb;

    public $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new CompanyRepository();
        $this->service = new CompanyService();
        $this->breadcrumb_add(route('company.index'), 'Компании');
    }

    /**
     * Страница со списком
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $filter_service = new CompanyListFilterService();
        $table_data = $this->repo->getTable();

        $sectors = SectorRepository::getAll();
        $partners = PartnerRepository::getAll();

        return view('pub.company.index', [
            'users' => [
                'created_by' => User::whereIn('id', $table_data['filter']['creator'] ?? [])->get(),
            ],
            'breadcrumbs' => $this->breadcrumb,
            'sectors' => $sectors,
            'partners' => $partners,
            'filter' => $filter_service->getFilter(),
            'filter_count' => $filter_service->getFilterCount(),
            'user' => []
        ]);
    }

    public function detail(Company $company)
    {
        // patch v29: просмотр состояния на момент (?at=)
        $state = EntityLogViewService::state($company, request('at'));
        if ($state) $company = $state['model'];

        $this->breadcrumb_add('', $company->name);
        $proposals = CompanyService::getProposalsGrouped($company);
        $contracts = ContractRepository::getGroupedProposal($company);

        $specsGrouped = ContractSpecificationRepository::getGrouped($company);

        // patch v48: блок «Проекты» и колонка «Проект» в таблице спецификаций
        $deal_projects = $this->dealProjects($company);

        return view('pub.company.detail', [
            'breadcrumbs' => $this->breadcrumb,
            'company' => $company,
            'log_root' => $company, // patch v29: кнопка журнала изменений в крошках
            'log_state' => $state,
            'proposals' => $proposals,
            'contracts' => $contracts,
            'specsGrouped' => $specsGrouped,
        ] + $deal_projects);
    }

    /**
     * Данные блока «Проекты» на странице компании (patch v48).
     *
     * Всё собирается пачками: спецификации всех проектов, их КП и сделки
     * Битрикса — по одному запросу, без обхода проектов по одному.
     *
     * Возвращает переменные шаблона:
     *  - deal_projects — проекты компании (действующие сверху), каждый —
     *    массив: project, specs, sums/paid по валютам, next_payment, deals;
     *  - spec_project_map — id спецификации → DealProject (колонка «Проект»);
     *  - specs_free — спецификации компании «В процессе» без действующего проекта;
     *  - spec_links / spec_proposals — привязки «спецификация → КП» и сами КП
     *    (последние редакции) для спецификаций компании и проектов;
     *  - deal_url — замыкание «id сделки → ссылка в Битрикс24».
     *
     * @param Company $company
     * @return array
     */
    protected function dealProjects(Company $company): array
    {
        $projects = DealProjectService::forCompany($company);

        // спецификации проектов — одним запросом, с платежами и конфигурациями
        $project_spec_ids = $projects
            ->flatMap(fn(DealProject $project) => $project->specifications->pluck('contract_specification_id'))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $specs = $project_spec_ids->isEmpty()
            ? collect()
            : ContractSpecification::whereIn('id', $project_spec_ids->all())
                ->with(['contract', 'currency', 'payments', 'project_configurations'])
                ->get()
                ->keyBy('id');

        // КП: для спецификаций компании (таблица ниже) и проектов разом
        $all_spec_ids = $company->specifications->pluck('id')
            ->map(fn($id) => (int) $id)
            ->merge($project_spec_ids)
            ->unique()
            ->values();

        $spec_links = ContractSpecificationProposal::whereIn('contract_specification_id', $all_spec_ids->all() ?: [0])->get();
        $spec_proposals = $spec_links->isEmpty()
            ? collect()
            : Proposal::latestIteration()->whereIn('group', $spec_links->pluck('proposal_group')->unique()->all())->get()->keyBy('group');

        // сделки Битрикса — другая база, один запрос на все проекты;
        // недоступность Битрикса не должна ронять карточку компании
        $deal_ids = $projects->flatMap(fn(DealProject $project) => $project->dealIds())->unique()->values();
        $crm_deals = collect();
        if ($deal_ids->isNotEmpty()) {
            try {
                $crm_deals = CrmDeal::whereIn('id', $deal_ids->all())->get()->keyBy('id');
            } catch (\Throwable $e) {
                report($e);
            }
        }

        $rows = $projects->map(function (DealProject $project) use ($specs, $crm_deals) {
            $project_specs = $project->specifications
                ->map(fn($link) => $specs->get((int) $link->contract_specification_id))
                ->filter()
                ->sortByDesc(fn(ContractSpecification $spec) => $spec->date_create ?? $spec->contract?->date)
                ->values();

            // суммы в разных валютах не складываются: группировка по валюте
            $sums = [];
            $paid = [];
            $next = null;

            foreach ($project_specs as $spec) {
                $currency = $spec->currency_slug ?: 'RUB';
                $symbol = $spec->currency?->symbol ?: $currency;

                // сумма спецификации, а если её не заполнили — по плановым платежам
                $amount = (float) $spec->amount ?: (float) $spec->payments->sum('amount_plan');

                $sums[$currency] = ['symbol' => $symbol, 'amount' => ($sums[$currency]['amount'] ?? 0) + $amount];
                $paid[$currency] = ['symbol' => $symbol, 'amount' => ($paid[$currency]['amount'] ?? 0)
                    + (float) $spec->payments->whereNotNull('date_fact')->sum('amount_fact')];

                // ближайший плановый платёж — самый ранний неоплаченный
                foreach ($spec->payments as $payment) {
                    if (!empty($payment->date_fact) || empty($payment->date_plan)) continue;

                    if (!$next || $payment->date_plan->lt($next['date'])) {
                        $next = ['date' => $payment->date_plan, 'amount' => (float) $payment->amount_plan, 'symbol' => $symbol];
                    }
                }
            }

            return [
                'project' => $project,
                'specs' => $project_specs,
                'sums' => $sums,
                'paid' => $paid,
                'next_payment' => $next,
                'deals' => collect($project->dealIds())
                    ->map(fn($id) => ['id' => (int) $id, 'deal' => $crm_deals->get($id)])
                    ->values(),
            ];
        });

        // спецификации компании в работе, не входящие ни в один действующий проект
        $active_spec_ids = DealProjectService::activeSpecIds();
        $specs_free = $company->specifications
            ->filter(fn(ContractSpecification $spec) => $spec->status == ContractSpecificationStatus::PROCESSING->value)
            ->reject(fn(ContractSpecification $spec) => in_array((int) $spec->id, $active_spec_ids, true))
            ->values();

        return [
            'deal_projects' => $rows,
            'spec_project_map' => DealProjectService::specProjectMap($company),
            'specs_free' => $specs_free,
            'spec_links' => $spec_links,
            'spec_proposals' => $spec_proposals,
            'deal_url' => fn($id) => CrmDealRegistryService::url($id),
        ];
    }

    public function create()
    {
        $this->breadcrumb_add(null, 'Создание');

        $sectors = SectorRepository::getAll();
        $partners = PartnerRepository::getAll();
        $countries = CountryRepository::getAll();


        return view('pub.company.create', [
            'breadcrumbs' => $this->breadcrumb,
            'sectors' => $sectors,
            'partners' => $partners,
            'countries' => $countries,
        ]);
    }



    /**
     * Форма редактирования
     *
     * @param \App\Modules\Pub\Company\Models\Company $company
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Company $company)
    {
        $this->breadcrumb_add('', 'Редактирование');

        $sectors = SectorRepository::getAll();
        $partners = PartnerRepository::getAll();
        $countries = CountryRepository::getAll();


        return view('pub.company.edit', [
            'breadcrumbs' => $this->breadcrumb,
            'sectors' => $sectors,
            'partners' => $partners,
            'countries' => $countries,
            'row' => $company
        ]);
    }

    /**
     * Обновление
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Modules\Pub\Company\Models\Company $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(CompanyUpdateRequest $request, Company $company)
    {
        //
        $company->user()->associate($request->input('user'));
        $company->course()->associate($request->input('course'));
        $company->fill($request->only($company->getFillable()))->save();

        return \Redirect::route('companys.index');
    }

    /**
     * Удаление
     *
     * @param \App\Modules\Pub\Company\Models\Company $company
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Company $company)
    {
        $company->delete();

        return \Redirect::route('companys.index');
    }

}
