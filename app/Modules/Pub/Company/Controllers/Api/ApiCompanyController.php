<?php

namespace App\Modules\Pub\Company\Controllers\Api;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Models\CompanyGrade;
use App\Modules\Pub\Company\Models\CompanyType;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Company\Requests\ListFilterRequest;
use App\Modules\Pub\Company\Services\CompanyListFilterService;
use App\Modules\Pub\Company\Services\CompanyService;
use Illuminate\Http\Request;

class ApiCompanyController
{
    public $repo;
    public function __construct()
    {
        $this->repo = new CompanyRepository();
        $this->service = new CompanyService();
    }

    public function list_table(Request $request)
    {
        $data = $this->service->tableDefault($request->only(['_token', 'sort', 'order', 'search', 'limit', 'offset']));

        return response()->json([
            "total" => $data['count_filter'],
            "totalNotFiltered" => $data['count'],
            "rows" => $data['rows']
        ]);
    }




    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'sector' => 'required|exists:sectors,id',
            'partner' => 'required|exists:partners,id',
            'country' => 'required|exists:countries,id',
        ]);

        CompanyRepository::create($request);

        return ['result' => 'success', 'url' => route('company.index')];
    }


    public function update(Company $company, Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'sector' => 'required|exists:sectors,id',
            'partner' => 'required|exists:partners,id',
            'country' => 'required|exists:countries,id',
        ]);

        CompanyRepository::update($company, $request);

        return ['result' => 'success', 'url' => route('company.index')];
    }

    public function delete(Company $company)
    {

        CompanyRepository::delete($company);

        return ['result' => 'success'];
    }



    public function filter(ListFilterRequest $request)
    {
        $service = new CompanyListFilterService($request->_token);
        $rules_count = $service->setFilter($request->validated());

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);
    }


    public function filterRemove(ListFilterRequest $request)
    {
        $service = new CompanyListFilterService($request->_token);
        $service->clearFilter();

        return response()->json([
            'result' => 'success'
        ]);
    }


}
