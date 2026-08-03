<?php

namespace App\Modules\Pub\Work\Controllers;

use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\EducationApplication\Models\EducationApplication;
use App\Modules\Pub\EducationApplication\Works\EducationApplicationListFilterService;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Work\Models\Work;
use App\Modules\Pub\Work\Models\WorkGrade;
use App\Modules\Pub\Work\Models\WorkType;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use App\Modules\Pub\Work\Services\WorkListFilterService;
use App\Modules\Pub\Work\Services\WorkService;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Work\Requests\WorkUpdateRequest;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class WorkController extends Controller
{
    use HasBreadcrumb;

    public $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new WorkRepository();
        $this->service = new WorkService();
        $this->breadcrumb_add(route('work.index'), 'Работы');
    }

    /**
     * Страница со списком
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index()
    {
        $filter_service = new WorkListFilterService();
        $table_data = $this->repo->getTable();


        return view('pub.work.index', [
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

        return view('pub.work.create', [
            'breadcrumbs' => $this->breadcrumb,
        ]);
    }



    /**
     * Форма редактирования
     *
     * @param \App\Modules\Pub\Work\Models\Work $work
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Work $work)
    {
        $this->breadcrumb_add('', 'Редактирование');

        return view('pub.work.edit', [
            'breadcrumbs' => $this->breadcrumb,
            'row' => $work
        ]);
    }

    /**
     * Обновление
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Modules\Pub\Work\Models\Work $service
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(WorkUpdateRequest $request, Work $work)
    {
        //
        $work->user()->associate($request->input('user'));
        $work->course()->associate($request->input('course'));
        $work->fill($request->only($work->getFillable()))->save();

        return \Redirect::route('work.index');
    }

    /**
     * Удаление
     *
     * @param \App\Modules\Pub\Work\Models\Work $work
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Work $work)
    {
        $work->delete();

        return \Redirect::route('work.index');
    }

    public function box_extended(Work $work = null)
    {
        if(empty($work)) abort(404);

        $template = View::make('pub.work.boxes.extended', [
            'title' => 'Просмотр',
            'work' => $work,
        ]);

        return $template;
    }

}
