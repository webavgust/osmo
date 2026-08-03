<?php

namespace App\Modules\Pub\Software\Controllers;

use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Softwares\EducationApplicationListFilterService;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Software\Models\Software;
use App\Modules\Pub\Software\Models\SoftwareGrade;
use App\Modules\Pub\Software\Models\SoftwareType;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\Software\Services\SoftwareListFilterService;
use App\Modules\Pub\Software\Services\SoftwareService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Software\Requests\SoftwareUpdateRequest;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class SoftwareController extends Controller
{
    use HasBreadcrumb;

    public $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new SoftwareRepository();
        $this->service = new SoftwareService();
        $this->breadcrumb_add(route('software.index'), 'Программное обеспечение');
    }

    /**
     * Страница со списком
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $filter_service = new SoftwareListFilterService();
        $table_data = $this->repo->getTable();


        return view('pub.software.index', [
            'users' => [
                'created_by' => User::whereIn('id', $table_data['filter']['creator'] ?? [])->get(),
            ],
            'breadcrumbs' => $this->breadcrumb,
            'filter' => $filter_service->getFilter(),
            'filter_count' => $filter_service->getFilterCount(),
            'user' => []
        ]);
    }


    public function create()
    {
        $this->breadcrumb_add(null, 'Создание');

        return view('pub.software.create', [
            'breadcrumbs' => $this->breadcrumb,
        ]);
    }



    /**
     * Форма редактирования
     *
     * @param \App\Modules\Pub\Software\Models\Software $software
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Software $software)
    {
        $this->breadcrumb_add('', 'Редактирование');

        return view('pub.software.edit', [
            'breadcrumbs' => $this->breadcrumb,
            'row' => $software
        ]);
    }

    /**
     * Обновление
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Modules\Pub\Software\Models\Software $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(SoftwareUpdateRequest $request, Software $software)
    {
        //
        $software->user()->associate($request->input('user'));
        $software->course()->associate($request->input('course'));
        $software->fill($request->only($software->getFillable()))->save();

        return \Redirect::route('software.index');
    }

    /**
     * Удаление
     *
     * @param \App\Modules\Pub\Software\Models\Software $software
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Software $software)
    {
        $software->delete();

        return \Redirect::route('software.index');
    }

    public function box_extended(Software $software = null)
    {
        if(empty($software)) abort(404);

        $template = View::make('pub.software.boxes.extended', [
            'title' => 'Просмотр',
            'software' => $software,
        ]);

        return $template;
    }

}
