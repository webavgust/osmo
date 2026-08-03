<?php

namespace App\Modules\Pub\AccessGroup\Controllers;

use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\Access\Requests\AccessUpdateRequest;
use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\AccessGroup\Requests\AccessGroupCreateRequest;
use App\Modules\Pub\AccessGroup\Requests\AccessGroupUpdateRequest;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\UserGroup\Models\UserGroup;

class AccessGroupController
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('access.index'), 'Доступы');
    }

    public function create() {
        $this->breadcrumb_add(null, 'Создание группы доступов');
        return view('pub::access_group.create', [
            'breadcrumbs' => $this->breadcrumb
        ]);
    }


    public function store(AccessGroupCreateRequest $request)
    {
        $group = new AccessGroup();
        $validated = $request->validated();

        $group->fill(
            $request->all()
        )->save();


        return redirect()->route('access.index')->with('show_id', $group->id);
    }


    public function edit(AccessGroup $group)
    {

        $this->breadcrumb_add(route('access.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Редактирование');

        return view('pub::access_group.edit')->with(['breadcrumbs' => $this->breadcrumb, 'group' => $group]);
    }

    public function update(AccessGroupUpdateRequest $request, AccessGroup $group)
    {
        $validated = $request->validated();
        $group->fill(
            $request->all()
        )->save();

        return redirect()->route('access.index')->with('show_id', $group->id);
    }

    public function destroy(AccessGroup $group)
    {
        $group->delete();
        return redirect()->route('access.index');
    }

}
