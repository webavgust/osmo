<?php

namespace App\Modules\Pub\User\Controllers\Api;

use App\Jobs\AjaxProgress\UsersSync;
use App\Modules\Pub\AjaxProgress\Models\AjaxProgress;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Request\SetSubUserRequest;
use App\Modules\Pub\User\Services\UserService;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class UserController extends Controller
{

    public $service;

    public function __construct()
    {
        $this->service = new UserService();
    }

    /**
     * Таблица со списком
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function list_table(Request $request)
    {
        $service = new UserService();
        $data = $service->tableDefault($request);

        return response()->json([
            "total" => $data['count_filter'],
            "totalNotFiltered" => $data['count'],
            "rows" => $data['rows']
        ]);
    }

    /**
     * Синхронизировать всех
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function sync_all()
    {
        $ajax = AjaxProgress::make()->fill([
            'finish_message' => 'Синхронизировано!',
            'target' => __METHOD__
        ]);
        $ajax->save();

        UsersSync::dispatch($ajax)->onQueue('database');

        return \Response::json(['uuid' => $ajax->uuid]);
    }

    /**
     * Установка руководителей
     *
     * @param SetSubUserRequest $request
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function parent_users_set(SetSubUserRequest $request, User $user)
    {
        $users = $request->input('user') ?? [];
        $user->parent_users()->sync($users);

        return \View::make('components.user.detail-sub-user-block', ['block' => 'parent', 'subUsers' => $user->parent_users]);
    }

    /**
     * Установка подчиненных
     *
     * @param SetSubUserRequest $request
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function sub_users_set(SetSubUserRequest $request, User $user)
    {
        $users = $request->input('user') ?? [];
        $user->sub_users()->sync($users);

        return \View::make('components.user.detail-sub-user-block', ['block' => 'sub', 'subUsers' => $user->sub_users]);
    }
}
