<?php

namespace App\Modules\Pub\Access\Controllers;

use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\Access\Requests\SetAccessUserRequest;
use App\Modules\Pub\Access\Services\AccessUserService;
use App\Modules\Pub\AccessGroup\Models\AccessGroup;
use App\Modules\Pub\Accessuser\Models\Accessuser;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\Useruser\Models\Useruser;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccessUserController
{
    use HasBreadcrumb;

    private $service;

    public function __construct()
    {
        $this->service = new AccessUserService(auth()->id());
        $this->breadcrumb_add(route('access.index'), 'Доступы', 0);
    }

    /**
     * Список пользователей для персональных доступов
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view_user()
    {
        $this->breadcrumb_add(route('access_set.user_list'), 'Персональные');

        return view('pub::user.access.list', [
            'breadcrumbs' => $this->breadcrumb
        ]);
    }

    /**
     * Просмотр персональных доступов для пользователя
     *
     * @param User $user Пользователь
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function view_user_detail(User $user)
    {
        $this->breadcrumb_add(route('access_set.user_list'), 'Персональные');
        $this->breadcrumb_add(null, $user->name);
        $access_save = [];
        $save = Access::all()->filter(function ($access) use ($user, &$access_save) {
            $type = $user->can_do($access, 1);

            if (!empty($type))
                $access_save[$access->id] = $type;
        });

        return view('pub::user.access.set', [
            'breadcrumbs' => $this->breadcrumb,
            'user' => $user,
            'access_save' => $access_save,
            'access_groups' => AccessGroup::with('accesses')->orderBy('sort')->get()
        ]);
    }

    /**
     * Установка доступа для пользователя и обновление кеша доступов
     *
     * @param User $user Пользователь
     * @param SetAccessUserRequest $request Request
     * @return \Illuminate\Http\JsonResponse
     */
    public function set_user(User $user, SetAccessUserRequest $request)
    {
        $data = collect($request->access)->filter(fn($item) => $item != 0)->map(fn($item) => ['mode' => $item]);
        $user->accesses()->sync($data);
        $user->can_recalc();

        return \Response::json(['result' => 'success']);
    }

    /**
     * Обновление доступа по запросу
     *
     * @param \Request $request
     * @return string[]
     */
    public function refresh(\Request $request)
    {
        $this->service->refresh();
        return ['result' => 'success'];
    }
}
