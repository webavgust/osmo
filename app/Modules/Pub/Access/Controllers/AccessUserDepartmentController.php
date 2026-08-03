<?php

namespace App\Modules\Pub\Access\Controllers;

use App\Modules\Pub\Access\Requests\SetAccessDepartmentRequest;
use App\Modules\Pub\Access\Requests\SetAccessGroupRequest;
use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Support\Facades\View;

class AccessUserDepartmentController
{
    use HasBreadcrumb;

    public function __construct()
    {
        // todo: проверка доступа на access.index
        $this->breadcrumb_add(route('access.index'), 'Доступы', 0);
    }

    /**
     * Просмотр списка подразделений
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view_department()
    {
        $this->breadcrumb_add(route('access_set.department_list'), 'Для подразделений');

        return view('pub::user_department.access.list', [
            'breadcrumbs' => $this->breadcrumb,
            'departments' => UserDepartment::withCount('users')
                ->withCount('accesses')
                ->orderBy('active', 'desc')
                ->orderBy('users_count', 'desc')
                ->get()
        ]);
    }

    /**
     * Просмотр детальной страницы подразделения
     *
     * @param UserDepartment $department Подразделение
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view_department_detail(UserDepartment $department)
    {
        $this->breadcrumb_add(route('access_set.department_list'), 'Для подразделений');
        $this->breadcrumb_add(null, $department->name);
        $department->load('accesses');

        return view('pub::user_department.access.set', [
            'breadcrumbs' => $this->breadcrumb,
            'department' => $department,
            'access_groups' => AccessGroup::with('accesses')->orderBy('sort')->get()
        ]);
    }

    /**
     * Установка доступа для подразделения
     *
     * @param UserDepartment $department Подразделение
     * @param SetAccessDepartmentRequest $request Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set_department(UserDepartment $department, SetAccessDepartmentRequest $request)
    {
        $data = collect($request->access)->filter(fn($item) => $item != 0)->map(fn($item) => ['mode' => $item]);
        $department->accesses()->sync($data);

        return \Response::json(['result' => 'success']);
    }

    /**
     * Просмотр подразделений пользователя
     *
     * @param User $user Пользователь
     * @return \Illuminate\Contracts\View\View
     */
    public function show_departments(User $user)
    {
        if (empty($user)) abort(404);

        return View::make('pub.user.access.show_departments', ['title' => 'Подразделения пользователя', 'rows' => $user->departments]);
    }
}
