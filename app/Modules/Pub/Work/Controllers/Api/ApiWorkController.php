<?php

namespace App\Modules\Pub\Work\Controllers\Api;

use App\Modules\Pub\Work\Models\Work;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use App\Modules\Pub\Work\Requests\ListFilterRequest;
use App\Modules\Pub\Work\Services\WorkListFilterService;
use App\Modules\Pub\Work\Services\WorkService;
use Illuminate\Http\Request;

class ApiWorkController
{
    public $repo;
    public function __construct()
    {
        $this->repo = new WorkRepository();
        $this->service = new WorkService();
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
            'group' => 'nullable|string',
            'extended' => 'nullable|string',
            'notice' => 'nullable|string',
            'count' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'lang' => 'nullable|string|in:ru,en',
        ]);

        WorkRepository::create($request);

        return ['result' => 'success', 'url' => route('work.index')];
    }


    public function update(Work $work, Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'group' => 'nullable|string',
            'extended' => 'nullable|string',
            'notice' => 'nullable|string',
            'count' => 'nullable|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'lang' => 'nullable|string|in:ru,en',
        ]);


        WorkRepository::update($work, $request);

        return ['result' => 'success', 'url' => route('work.index')];
    }

    public function delete(Work $work)
    {

        WorkRepository::delete($work);

        return ['result' => 'success'];
    }



    public function filter(ListFilterRequest $request)
    {
        $service = new WorkListFilterService($request->_token);
        $rules_count = $service->setFilter($request->validated());

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);
    }


    public function filterRemove(ListFilterRequest $request)
    {
        $service = new WorkListFilterService($request->_token);
        $service->clearFilter();

        return response()->json([
            'result' => 'success'
        ]);
    }


}
