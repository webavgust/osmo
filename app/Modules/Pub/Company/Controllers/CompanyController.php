<?php

namespace App\Modules\Pub\Company\Controllers;

use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\ContractSpecification\Repository\ContractSpecificationRepository;
use App\Modules\Pub\Country\Repositories\CountryRepository;
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
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
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
        $this->breadcrumb_add('', $company->name);
        $proposals = CompanyService::getProposalsGrouped($company);
        $contracts = ContractRepository::getGroupedProposal($company);

        $specsGrouped = ContractSpecificationRepository::getGrouped($company);

        return view('pub.company.detail', [
            'breadcrumbs' => $this->breadcrumb,
            'company' => $company,
            'proposals' => $proposals,
            'contracts' => $contracts,
            'specsGrouped' => $specsGrouped,
        ]);
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
