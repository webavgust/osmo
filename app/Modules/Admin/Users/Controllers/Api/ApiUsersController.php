<?php

namespace App\Modules\Admin\Users\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Users\Services\AdminUserService;
use App\Modules\Pub\User\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * AJAX админ-панели: пользователи (patch v28, этап B).
 *
 * Правила — в AdminUserService, здесь только разбор запроса и ответ в формате
 * `{result: 'success'|'error', message}` со статусом 200: страница показывает
 * toastr и не падает в 500-ю.
 */
class ApiUsersController extends Controller
{
    /** Поля формы попапа */
    protected const FIELDS = [
        'name', 'last_name', 'second_name', 'login', 'email', 'password',
        'work_position', 'work_department', 'personal_mobile',
        'active', 'is_admin', 'ui_theme_switch', 'log_view',
    ];

    /**
     * Создать пользователя
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        return $this->run(function () use ($request) {
            $user = AdminUserService::create($request->only(self::FIELDS), auth()->user());

            return [
                'message' => 'Пользователь ' . ($user->full_name ?: $user->login) . ' создан',
                'id' => $user->id,
                'url' => route('admin.users.show', $user->id),
            ];
        });
    }

    /**
     * Изменить пользователя
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, User $user)
    {
        return $this->run(function () use ($request, $user) {
            AdminUserService::update($user, $request->only(self::FIELDS), auth()->user());

            return ['message' => 'Пользователь сохранён', 'id' => $user->id];
        });
    }

    /**
     * Разрешить / запретить переключение темы оформления
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function theme(Request $request, User $user)
    {
        return $this->run(function () use ($request, $user) {
            $value = $request->boolean('value');
            AdminUserService::setThemeSwitch($user, $value);

            return [
                'message' => $value
                    ? 'Переключение на старую тему разрешено'
                    : 'Теперь пользователь всегда работает в Metronic',
                'id' => $user->id,
                'value' => $value,
            ];
        });
    }

    /**
     * Открыть / скрыть журнал изменений сущностей (patch v29)
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function logView(Request $request, User $user)
    {
        return $this->run(function () use ($request, $user) {
            $value = $request->boolean('value');
            AdminUserService::setLogView($user, $value);

            return [
                // у админа панели журнал открыт независимо от флага
                'message' => $user->is_admin
                    ? 'Админ видит журнал всегда'
                    : ($value ? 'Журнал изменений открыт' : 'Журнал изменений скрыт'),
                'id' => $user->id,
                'value' => $value,
            ];
        });
    }

    /**
     * Мягко удалить пользователя
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function delete(Request $request, User $user)
    {
        return $this->run(function () use ($user) {
            AdminUserService::delete($user, auth()->user());

            return ['message' => 'Пользователь ' . ($user->full_name ?: $user->login) . ' удалён', 'id' => $user->id];
        });
    }

    /**
     * Восстановить удалённого пользователя
     *
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore(Request $request, User $user)
    {
        return $this->run(function () use ($user) {
            AdminUserService::restore($user);

            return ['message' => 'Пользователь ' . ($user->full_name ?: $user->login) . ' восстановлен', 'id' => $user->id];
        });
    }

    /**
     * Выполнить действие и превратить ошибку в понятный ответ
     *
     * @param callable $action
     * @return \Illuminate\Http\JsonResponse
     */
    protected function run(callable $action)
    {
        try {
            $result = $action();
        } catch (ValidationException $e) {
            // все ошибки формы разом: тостер выводит HTML, текст экранируем
            return response()->json([
                'result' => 'error',
                'message' => collect($e->errors())->flatten()->map(fn($message) => e($message))->implode('<br>')
                    ?: e($e->getMessage()),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'result' => 'error',
                'message' => 'Не получилось выполнить действие: ' . e($e->getMessage()),
            ]);
        }

        return response()->json(array_merge(['result' => 'success'], $result));
    }
}
