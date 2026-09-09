<?php

namespace App\Modules\Pub\Partner\Controllers\Api;

use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use App\Modules\Pub\Partner\Models\PartnerType;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Partner\Requests\ListFilterRequest;
use App\Modules\Pub\Partner\Services\PartnerCrmCompanyService;
use App\Modules\Pub\Partner\Services\PartnerListFilterService;
use App\Modules\Pub\Partner\Services\PartnerService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiPartnerController
{
    public $repo;
    public function __construct()
    {
        $this->repo = new PartnerRepository();
        $this->service = new PartnerService();
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
            'active' => 'nullable|bool',
            'name' => 'required|string',
            'type' => 'required|string',
            'grade' => 'required|string',
            'contact' => 'nullable|string',
            'phone' => 'nullable|string',
            'region' => 'nullable',
            'crm_companies' => 'nullable|array',
            'crm_companies.*' => 'integer',
        ]);

        if(!PartnerType::from($request->input('type'))) return ['result' => 'error'];
        if(!PartnerGrade::from($request->input('grade'))) return ['result' => 'error'];

        $partner = PartnerRepository::create($request);

        // сопоставление с Битрикс24 (patch v23)
        try {
            (new PartnerCrmCompanyService())->sync($partner, $request->input('crm_companies', []));
        } catch (ValidationException $e) {
            return ['result' => 'error', 'message' => $e->validator->errors()->first()];
        }

        return ['result' => 'success', 'url' => route('partner.detail', $partner)];
    }


    public function update(Partner $partner, Request $request)
    {
        $request->validate([
            'active' => 'nullable|bool',
            'name' => 'required|string',
            'type' => 'required|string',
            'grade' => 'required|string',
            'contact' => 'nullable|string',
            'phone' => 'nullable|string',
            'region' => 'nullable',
            'crm_companies' => 'nullable|array',
            'crm_companies.*' => 'integer',
        ]);

        if(!PartnerType::from($request->input('type'))) return ['result' => 'error'];
        if(!PartnerGrade::from($request->input('grade'))) return ['result' => 'error'];

        // сопоставление с Битрикс24 (patch v23): проверяем до сохранения партнёра,
        // чтобы занятая компания не давала «наполовину сохранённую» форму
        try {
            (new PartnerCrmCompanyService())->sync($partner, $request->input('crm_companies', []));
        } catch (ValidationException $e) {
            return ['result' => 'error', 'message' => $e->validator->errors()->first()];
        }

        PartnerRepository::update($partner, $request);

        return ['result' => 'success', 'url' => route('partner.detail', $partner)];
    }

    public function delete(Partner $partner)
    {

        PartnerRepository::delete($partner);

        return ['result' => 'success'];
    }



    public function filter(ListFilterRequest $request)
    {
        $service = new PartnerListFilterService($request->_token);
        $rules_count = $service->setFilter($request->validated());

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);
    }


    public function filterRemove(ListFilterRequest $request)
    {
        $service = new PartnerListFilterService($request->_token);
        $service->clearFilter();

        return response()->json([
            'result' => 'success'
        ]);
    }


}
