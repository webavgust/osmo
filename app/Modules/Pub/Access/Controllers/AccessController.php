<?php

namespace App\Modules\Pub\Access\Controllers;

use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\Access\Requests\AccessCreateRequest;
use App\Modules\Pub\Access\Requests\AccessUpdateRequest;
use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class AccessController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('access.index'), 'Доступы');
    }

    /**
     * Страница с доступами
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        $groups = AccessGroup::with('accesses')->orderBy('sort', 'asc')->get();

        return View::make('pub.access.index', [
            'groups' => $groups,
            'breadcrumbs' => $this->breadcrumb
        ]);
    }

    /**
     * Страница создания доступа внутри группы
     *
     * @param AccessGroup $group Группа доступов
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(AccessGroup $group)
    {
        $this->authorize('access_create');
        if (empty($group->id)) abort(404);
        $this->breadcrumb_add(route('access.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Создание');

        return view('pub::access.create')->with(['breadcrumbs' => $this->breadcrumb, 'group' => $group]);
    }

    /**
     * Форма редактирования доступа
     *
     * @param Access $access Доступ
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function edit(Access $access)
    {
        $this->authorize('access_create');
        $group = $access->access_group()->first();

        $this->breadcrumb_add(route('access.index', ['hl' => $group->id]), $group->name);
        $this->breadcrumb_add(null, 'Редактирование');
        return view('pub::access.edit')->with(['breadcrumbs' => $this->breadcrumb, 'access' => $access, 'group' => $group]);
    }

    /**
     * Создание нового доступа
     *
     * @param AccessCreateRequest $request Request
     * @param AccessGroup $group Группа доступов
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(AccessCreateRequest $request, AccessGroup $group)
    {
        $access = new Access();
        $access->fill(
            $request->all()
        );
        $group->accesses()->save($access);

        return redirect()->route('access.index')->with('show_id', $group->id);
    }

    /**
     * Обновление доступа
     *
     * @param AccessUpdateRequest $request Request
     * @param Access $access Доступ
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(AccessUpdateRequest $request, Access $access)
    {
        $validated = $request->validated();
        $group = $access->access_group()->first();
        $access->fill(
            $request->all()
        )->save();

        return redirect()->route('access.index')->with('show_id', $group->id);
    }

    /**
     * Уничтожение доступа
     *
     * @param Access $access
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Access $access)
    {
        $group = $access->access_group()->first();
        $access->delete();

        // TODO: Удалить привязки пользователей и групп

        return redirect()->route('access.index')->with('show_id', $group->id);
    }
}
