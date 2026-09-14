<?php

namespace App\Modules\Admin\Users\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Users\Services\AdminUserService;
use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Админ-панель: пользователи (patch v28, этап B).
 *
 * Доступ — Gate admin_panel на всю группу Admin (config/modular.php).
 * Сохранение и действия — AJAX в Api\ApiUsersController.
 */
class UsersController extends Controller
{
    use HasBreadcrumb;

    public function __construct()
    {
        $this->breadcrumb_add(route('admin.index'), 'Админ-панель');
    }

    /**
     * Список пользователей
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function index(Request $request)
    {
        $this->breadcrumb_add(route('admin.users.index'), 'Пользователи');

        $params = AdminUserService::params($request->all());
        $users = AdminUserService::list($params);

        return View::make('admin.users.index', [
            'breadcrumbs' => $this->breadcrumb,
            'params' => $params,
            'states' => AdminUserService::STATES,
            'users' => $users,
            'last_logins' => AdminUserService::lastLogins($users->pluck('id')->all()),
            'trashed_count' => User::onlyTrashed()->count(),
        ]);
    }

    /**
     * Карточка пользователя (открывается и для удалённого)
     *
     * @param User $user
     * @return \Illuminate\Contracts\View\View
     */
    public function show(User $user)
    {
        $this->breadcrumb_add(route('admin.users.index'), 'Пользователи');
        $this->breadcrumb_add('', $user->full_name ?: $user->login);

        return View::make('admin.users.show', [
            'breadcrumbs' => $this->breadcrumb,
            'user' => $user,
            'is_self' => (int) $user->id === (int) auth()->id(),
            'last_login' => AdminUserService::lastLogins([$user->id])->get($user->id),
            'attempts' => AdminUserService::attempts($user),
        ]);
    }

    /**
     * Попап создания / редактирования пользователя
     *
     * @param User|null $user null — новый пользователь
     * @return \Illuminate\Contracts\View\View
     */
    public function box_form(User $user = null)
    {
        return View::make('admin.users.boxes.form', [
            'title' => $user ? 'Пользователь: ' . ($user->full_name ?: $user->login) : 'Новый пользователь',
            'user' => $user,
            'is_self' => $user && (int) $user->id === (int) auth()->id(),
        ]);
    }
}
