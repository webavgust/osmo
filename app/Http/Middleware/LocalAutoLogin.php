<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Локальный автологин для разработки.
 *
 * ВНИМАНИЕ. Это инструмент разработки, обходящий авторизацию. Он не должен
 * срабатывать на сервере. APP_ENV на сервере и локально одинаково равен
 * `development`, поэтому окружение здесь ни при чём — защита построена на
 * признаках, которых на сервере нет:
 *
 *   1. Флаг LOCAL_AUTOLOGIN в .env. Файл .env в git не хранится и из деплоя
 *      исключён, поэтому флаг не уезжает на сервер.
 *   2. Порт базы данных. Локальная копия работает на 33306, продакшн — на 3306.
 *      Даже если .env с флагом случайно окажется на сервере, автологин
 *      останется выключенным.
 *
 * Оба условия должны выполниться одновременно. Логин пользователя задаётся
 * в LOCAL_AUTOLOGIN_ID (по умолчанию 1).
 */
class LocalAutoLogin
{
    /** Пути, на которых автологин не нужен: форма входа и выход */
    protected array $except = [
        'auth',
        'logout',
    ];

    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($this->enabled() && Auth::guest() && !$this->isExcluded($request)) {
            $id = (int) env('LOCAL_AUTOLOGIN_ID', 1);
            Auth::loginUsingId($id);
        }

        return $next($request);
    }

    /**
     * Автологин разрешён только при обоих локальных признаках сразу
     *
     * @return bool
     */
    protected function enabled(): bool
    {
        $flag = filter_var(env('LOCAL_AUTOLOGIN', false), FILTER_VALIDATE_BOOLEAN);
        $port = (string) config('database.connections.' . config('database.default') . '.port');

        return $flag && $port === '33306';
    }

    /**
     * Путь исключён из автологина (форма входа, выход)
     *
     * @param Request $request
     * @return bool
     */
    protected function isExcluded(Request $request): bool
    {
        foreach ($this->except as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }
}
