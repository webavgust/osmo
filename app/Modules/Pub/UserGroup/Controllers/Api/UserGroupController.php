<?php

namespace App\Modules\Pub\UserGroup\Controllers\Api;

use App\Facades\Tools;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\User\Controllers\Api\UserController;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Request\AuthRequest;
use App\Modules\Pub\User\Services\UserService;
use App\Modules\Pub\UserGroup\Repositories\UserGroupRepository;
use App\Modules\Pub\UserGroup\Services\UserGroupService;
use App\Services\AjaxToken\AjaxToken;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class UserGroupController extends Controller
{

    public function list_table(Request $request) {
        $user_controller = new UserController();
        $user_controller->list_table($request);
    }

}
