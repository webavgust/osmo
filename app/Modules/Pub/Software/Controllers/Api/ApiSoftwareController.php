<?php

namespace App\Modules\Pub\Software\Controllers\Api;

use App\Modules\Pub\Software\Models\Software;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\Software\Requests\ListFilterRequest;
use App\Modules\Pub\Software\Services\SoftwareListFilterService;
use App\Modules\Pub\Software\Services\SoftwareService;
use Illuminate\Http\Request;

class ApiSoftwareController
{
    public $repo;
    public function __construct()
    {
        $this->repo = new SoftwareRepository();
        $this->service = new SoftwareService();
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
            'extended' => 'nullable|string',
            'notice' => 'nullable|string',
            'count' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'cb_nds' => 'nullable|bool',
        ]);

        SoftwareRepository::create($request);

        return ['result' => 'success', 'url' => route('software.index')];
    }


    public function update(Software $software, Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'extended' => 'nullable|string',
            'notice' => 'nullable|string',
            'count' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'cb_nds' => 'nullable|bool',
        ]);


        SoftwareRepository::update($software, $request);

        return ['result' => 'success', 'url' => route('software.index')];
    }

    public function delete(Software $software)
    {

        SoftwareRepository::delete($software);

        return ['result' => 'success'];
    }



    public function filter(ListFilterRequest $request)
    {
        $service = new SoftwareListFilterService($request->_token);
        $rules_count = $service->setFilter($request->validated());

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);
    }


    public function filterRemove(ListFilterRequest $request)
    {
        $service = new SoftwareListFilterService($request->_token);
        $service->clearFilter();

        return response()->json([
            'result' => 'success'
        ]);
    }


}
