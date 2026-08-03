<?php

namespace App\Modules\Pub\Access\Controllers;

use App\Modules\Pub\Access\Requests\SetAccessGroupRequest;
use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Support\Facades\View;

class AccessUserGroupController
{
    use HasBreadcrumb;

    public function __construct()
    {
        // todo: проверка доступа на access.index
        $this->breadcrumb_add(route('access.index'), 'Доступы', 0);
    }

    /**
     * Просмотр списка групп
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view_group()
    {
        $this->breadcrumb_add(route('access_set.group_list'), 'Для групп');

        return view('pub::user_group.access.list', [
            'breadcrumbs' => $this->breadcrumb,
            'groups' => UserGroup::withCount('users')->withCount('accesses')->orderBy('users_count', 'desc')->get()
        ]);
    }

    /**
     * Просмотр детальной страницы группы
     *
     * @param UserGroup $group Группа пользователей
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view_group_detail(UserGroup $group)
    {
        $this->breadcrumb_add(route('access_set.group_list'), 'Для групп');
        $this->breadcrumb_add(null, $group->name);
        $group->load('accesses');

        return view('pub::user_group.access.set', [
            'breadcrumbs' => $this->breadcrumb,
            'group' => $group,
            'access_groups' => AccessGroup::with('accesses')->orderBy('sort')->get()
        ]);
    }

    /**
     * Установка доступа для группы
     *
     * @param UserGroup $group Группа пользователей
     * @param SetAccessGroupRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set_group(UserGroup $group, SetAccessGroupRequest $request)
    {
        $data = collect($request->access)->filter(fn($item) => $item != 0)->map(fn($item) => ['mode' => $item]);
        $group->accesses()->sync($data);

        return \Response::json(['result' => 'success']);
    }

    /**
     * Просмотр групп пользователя
     *
     * @param User $user Пользователь
     * @return \Illuminate\Contracts\View\View
     */
    public function show_groups(User $user)
    {
        if (empty($user)) abort(404);

        return View::make('pub.user.access.show_groups', ['title' => 'Группы пользователя', 'rows' => $user->groups]);
    }
}
