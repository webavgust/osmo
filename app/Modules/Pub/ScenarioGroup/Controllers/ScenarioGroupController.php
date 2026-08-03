<?php

namespace App\Modules\Pub\ScenarioGroup\Controllers;

use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\Scenario\Requests\ScenarioUpdateRequest;
use App\Modules\Pub\ScenarioGroup\Models\ScenarioGroup;
use App\Modules\Pub\ScenarioGroup\Requests\ScenarioGroupCreateRequest;
use App\Modules\Pub\ScenarioGroup\Requests\ScenarioGroupUpdateRequest;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\UserGroup\Models\UserGroup;

class ScenarioGroupController
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('scenario.index'), 'Сценарии');
    }

    public function create() {
        $this->breadcrumb_add(null, 'Создание группы сценариев');
        return view('pub.scenario_group.create', [
            'breadcrumbs' => $this->breadcrumb
        ]);
    }


    public function store(ScenarioGroupCreateRequest $request)
    {
        $group = new ScenarioGroup();
        $validated = $request->validated();

        $group->fill(
            $request->all()
        )->save();


        return redirect()->route('scenario.index')->with('show_id', $group->id);
    }


    public function edit(ScenarioGroup $group)
    {

        $this->breadcrumb_add(route('scenario.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Редактирование');

        return view('pub.scenario_group.edit')->with(['breadcrumbs' => $this->breadcrumb, 'group' => $group]);
    }

    public function update(ScenarioGroupUpdateRequest $request, ScenarioGroup $group)
    {
        $validated = $request->validated();
        $group->fill(
            $request->all()
        )->save();

        return redirect()->route('scenario.index')->with('show_id', $group->id);
    }

    public function destroy(ScenarioGroup $group)
    {
        $group->delete();
        return redirect()->route('scenario.index');
    }

}
