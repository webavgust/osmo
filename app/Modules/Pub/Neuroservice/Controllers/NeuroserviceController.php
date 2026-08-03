<?php

namespace App\Modules\Pub\Neuroservice\Controllers;

use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Repositories\NeuroserviceRepository;
use App\Modules\Pub\Neuroservice\Requests\NeuroserviceCreateRequest;
use App\Modules\Pub\Neuroservice\Requests\NeuroserviceUpdateRequest;
use App\Modules\Pub\NeuroserviceGroup\Models\NeuroserviceGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class NeuroserviceController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('neuroservice.index'), 'Нейросервисы');
    }

    /**
     * Страница с доступами
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $groups = NeuroserviceGroup::with('neuroservices')->orderBy('sort', 'asc')->get();
        $multiplier = \App\Modules\Pub\Constant\Models\Constant::get('neuroservice_unlimited_multiplier');

        return View::make('pub.neuroservice.index', [
            'groups' => $groups,
            'multiplier' => $multiplier,
            'breadcrumbs' => $this->breadcrumb
        ]);
    }

    /**
     * Страница создания доступа внутри группы
     *
     * @param NeuroserviceGroup $group Группа доступов
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Neuroservice\AuthorizationException
     */
    public function create(NeuroserviceGroup $group)
    {
        if (empty($group->id)) abort(404);
        $this->breadcrumb_add(route('neuroservice.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Создание');

        $unlimit_rate = \App\Modules\Pub\Constant\Models\Constant::get('neuroservice_unlimited_multiplier') * 100;



        return view('pub.neuroservice.create',
            [
                'breadcrumbs' => $this->breadcrumb,
                'group' => $group,
                'unlimit_rate' => $unlimit_rate,
            ]
        );
    }

    /**
     * Форма редактирования доступа
     *
     * @param Neuroservice $neuroservice Доступ
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Neuroservice\AuthorizationException
     */
    public function edit(Neuroservice $neuroservice)
    {
        $group = $neuroservice->neuroservice_group()->first();

        $this->breadcrumb_add(route('neuroservice.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Редактирование');

        $unlimit_rate = \App\Modules\Pub\Constant\Models\Constant::get('neuroservice_unlimited_multiplier') * 100;

        return view('pub.neuroservice.edit',[
            'breadcrumbs' => $this->breadcrumb,
            'neuroservice' => $neuroservice,
            'unlimit_rate' => $unlimit_rate,
            'group' => $group
        ]);
    }

    /**
     * Создание нового доступа
     *
     * @param NeuroserviceCreateRequest $request Request
     * @param NeuroserviceGroup $group Группа доступов
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(NeuroserviceCreateRequest $request, NeuroserviceGroup $group)
    {
        $neuroservice = new Neuroservice();
        $neuroservice->fill(
            $request->all()
        );
        $group->neuroservices()->save($neuroservice);

        return redirect()->route('neuroservice.index', ['hl' => $group->id]);
    }

    /**
     * Обновление доступа
     *
     * @param NeuroserviceUpdateRequest $request Request
     * @param Neuroservice $neuroservice Доступ
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(NeuroserviceUpdateRequest $request, Neuroservice $neuroservice)
    {
        $neuroservice = NeuroserviceRepository::update(neuroservice: $neuroservice, data: $request->all());

        $group = $neuroservice->neuroservice_group()->first();
        return redirect()->route('neuroservice.index', ['hl' => $group->id]);
    }

    /**
     * Уничтожение доступа
     *
     * @param Neuroservice $neuroservice
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Neuroservice $neuroservice)
    {
        $group = $neuroservice->neuroservice_group()->first();
        $neuroservice->delete();

        // TODO: Удалить привязки пользователей и групп

        return redirect()->route('neuroservice.index', ['hl' => $group->id]);
    }

    public function box_scenarios(Neuroservice $neuroservice)
    {
        $template = View::make('pub.neuroservice.boxes.scenarios', [
            'title' => 'Просмотр сценариев для нейросервиса "' .  $neuroservice->name . '"',
            'neuroservice' => $neuroservice,
        ]);

        return $template;
    }


}
