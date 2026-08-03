<?php

namespace App\Modules\Pub\UserGroup\Controllers;

use App\Facades\Tools;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Request\AuthRequest;
use App\Modules\Pub\User\Services\UserService;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use App\Modules\Pub\UserGroup\Services\UserGroupService;
use App\Services\AjaxToken\AjaxToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\View;

class UserGroupController extends Controller
{
    use HasBreadcrumb;

    private $service;
    private $repo;

    public function __construct()
    {
        $this->breadcrumb_add(route('user_group.list'), 'Группы пользователей');
        $this->service = new UserGroupService();
        $this->repo = new UserGroupRepository();
    }

    public function list() {
        return view('pub::user_group.list', [
            'breadcrumbs' => $this->breadcrumb,
            'groups' => $this->repo->getAllWithUsersCount()
        ]);
    }


    public function detail(UserGroup $userGroup) {
        $this->breadcrumb_add('', $userGroup->name);


        return view('pub::user_group.detail', [
            'breadcrumbs' => $this->breadcrumb,
            'group' => $userGroup
        ]);
    }


}
