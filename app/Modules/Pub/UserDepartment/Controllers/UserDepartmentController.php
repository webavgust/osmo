<?php

namespace App\Modules\Pub\UserDepartment\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserDepartment\Repositories\UserDepartmentRepository;
use App\Modules\Pub\UserDepartment\Services\UserDepartmentService;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use App\Modules\Pub\UserGroup\Services\UserGroupService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\View;

class UserDepartmentController extends Controller
{
    use HasBreadcrumb;

    private $repo;

    public function __construct()
    {
        $this->breadcrumb_add(route('user_department.list'), 'Подразделения пользователей');
        $this->repo = new UserDepartmentRepository();
    }

    /**
     * Список
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function list()
    {
        return view('pub::user_department.list', [
            'breadcrumbs' => $this->breadcrumb,
            'groups' => $this->repo->getAllWithUsersCount()
        ]);
    }

    /**
     * Детальная страница
     *
     * @param UserDepartment $userDepartment
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function detail(UserDepartment $userDepartment)
    {
        $this->breadcrumb_add('', $userDepartment->name);

        return view('pub::user_department.detail', [
            'breadcrumbs' => $this->breadcrumb,
            'department' => $userDepartment
        ]);
    }



    /**
     * Sidebar управление подписантами
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function sidebar_agreements(\Illuminate\Http\Request $request, UserDepartment $userDepartment)
    {
        $template = View::make('pub.user_department.sidebars.staff', [
            'title' => 'Управление согласовантами',
            'users' => UserGroupRepository::getAgreementers(),
            'selected' => $userDepartment->agreementers->pluck('id')->toArray(),
            'row' => $userDepartment
        ]);

        return $template;
    }
}
