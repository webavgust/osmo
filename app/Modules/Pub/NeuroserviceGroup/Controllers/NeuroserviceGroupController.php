<?php

namespace App\Modules\Pub\NeuroserviceGroup\Controllers;

use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Requests\NeuroserviceUpdateRequest;
use App\Modules\Pub\NeuroserviceGroup\Models\NeuroserviceGroup;
use App\Modules\Pub\NeuroserviceGroup\Requests\NeuroserviceGroupCreateRequest;
use App\Modules\Pub\NeuroserviceGroup\Requests\NeuroserviceGroupUpdateRequest;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\UserGroup\Models\UserGroup;

class NeuroserviceGroupController
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('neuroservice.index'), 'Нейросервисы');
    }

    public function create() {
        $this->breadcrumb_add(null, 'Создание группы нейросервисов');
        return view('pub.neuroservice_group.create', [
            'breadcrumbs' => $this->breadcrumb
        ]);
    }


    public function store(NeuroserviceGroupCreateRequest $request)
    {
        $group = new NeuroserviceGroup();
        $validated = $request->validated();

        $group->fill(
            $request->all()
        )->save();


        return redirect()->route('neuroservice.index')->with('show_id', $group->id);
    }


    public function edit(NeuroserviceGroup $group)
    {

        $this->breadcrumb_add(route('neuroservice.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Редактирование');

        return view('pub.neuroservice_group.edit')->with(['breadcrumbs' => $this->breadcrumb, 'group' => $group]);
    }

    public function update(NeuroserviceGroupUpdateRequest $request, NeuroserviceGroup $group)
    {
        $validated = $request->validated();
        $group->fill(
            $request->all()
        )->save();

        return redirect()->route('neuroservice.index')->with('show_id', $group->id);
    }

    public function destroy(NeuroserviceGroup $group)
    {
        $group->delete();
        return redirect()->route('neuroservice.index');
    }

}
