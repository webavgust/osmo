<?php

namespace App\Modules\Pub\Scenario\Controllers;

use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Repositories\NeuroserviceRepository;
use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Scenario\Requests\ScenarioCreateRequest;
use App\Modules\Pub\Scenario\Requests\ScenarioUpdateRequest;
use App\Modules\Pub\ScenarioGroup\Models\ScenarioGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class ScenarioController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('scenario.index'), 'Сценарии');
    }

    /**
     * Страница с доступами
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $groups = ScenarioGroup::with('scenarios')->orderBy('sort', 'asc')->get();

        return View::make('pub.scenario.index', [
            'groups' => $groups,
            'breadcrumbs' => $this->breadcrumb
        ]);
    }

    /**
     * Страница создания доступа внутри группы
     *
     * @param ScenarioGroup $group Группа доступов
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Scenario\AuthorizationException
     */
    public function create(ScenarioGroup $group)
    {
        if (empty($group->id)) abort(404);
        $this->breadcrumb_add(route('scenario.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Создание');

        $services = NeuroserviceRepository::getAll();
        $services->map(function($service) {
            $service->name = "[{$service->neuroservice_group->name}] " . $service->name;
            return $service;
        });
        $costs = NeuroserviceRepository::getCosts();


        return view('pub.scenario.create',
            [
                'breadcrumbs' => $this->breadcrumb,
                'group' => $group,
                'services' => $services,
                'costs' => $costs,
            ]
        );
    }

    /**
     * Форма редактирования доступа
     *
     * @param Scenario $scenario Доступ
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Scenario\AuthorizationException
     */
    public function edit(Scenario $scenario)
    {
        $group = $scenario->scenario_group()->first();

        $this->breadcrumb_add(route('scenario.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, $scenario->name);
        $this->breadcrumb_add(null, 'Редактирование');


        $services = NeuroserviceRepository::getAll();
        $services->map(function($service) {
            $service->name = "[{$service->neuroservice_group->name}] " . $service->name;
            return $service;
        });
        $cost_rules = collect($scenario->cost_rules);

        return view('pub.scenario.edit',[
            'breadcrumbs' => $this->breadcrumb,
            'scenario' => $scenario,
            'services' => $services,
            'cost_rules' => $cost_rules,
            'groups' => ScenarioGroup::all(),
        ]);
    }

    /**
     * Создание нового доступа
     *
     * @param ScenarioCreateRequest $request Request
     * @param ScenarioGroup $group Группа доступов
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(ScenarioCreateRequest $request, ScenarioGroup $group)
    {
        ScenarioRepository::create(data: $request->all(), group: $group);

        return redirect()->route('scenario.index', ['hl' => $group->id]);
    }

    /**
     * Обновление доступа
     *
     * @param ScenarioUpdateRequest $request Request
     * @param Scenario $scenario Доступ
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(ScenarioUpdateRequest $request, Scenario $scenario)
    {
        ScenarioRepository::update(scenario: $scenario, data: $request->all());

        return redirect()->route('scenario.index', ['hl' => $scenario->scenario_group->id]);
    }

    /**
     * Уничтожение доступа
     *
     * @param Scenario $scenario
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Scenario $scenario)
    {
        $group = $scenario->scenario_group()->first();
        $scenario->delete();

        // TODO: Удалить привязки пользователей и групп

        return redirect()->route('scenario.index', ['hl' => $group->id]);
    }
}
