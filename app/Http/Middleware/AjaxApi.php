<?php

namespace App\Http\Middleware;

use App\Services\AjaxToken\AjaxToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AjaxApi
{
    /**
     * Проверка AJAX-запроса к API: запрос обязан нести _token, совпадающий
     * с ajax_token пользователя текущей сессии.
     *
     * Без _token — 403. Без сессии (гость, истёкшая сессия) — тот же ответ,
     * что и при чужом токене: {error: 'auth'} (по нему пульс страницы
     * spider.tick уводит на форму входа). Входа по одному токену нет
     * намеренно (patch v36): ajax_token ходит в адресах запросов и живёт
     * до выхода из системы.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse|\Illuminate\Http\Response
     */

    public function handle(Request $request, Closure $next)
    {
        if(empty($request->input('_token')))
            abort(403);

        // без сессии по токену не входим — отвечаем как на чужой токен
        $user = $request->user();
        if(empty($user))
            return response()->json(['error' => 'auth']);

        if($user->ajax_token !== $request->input('_token'))
            return response()->json(['error' => 'auth']);

        return $next($request);
    }
}
