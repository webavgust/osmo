<?php

namespace App\Http\Middleware;

use App\Modules\Pub\EntityLog\Services\EntityLogService;
use Closure;
use Illuminate\Http\Request;

/**
 * Запись журнала изменений в конце запроса (patch v29).
 *
 * Стоит первым в группах web и api, поэтому отрабатывает после контроллера
 * и остальных middleware: по всем «грязным» корням снимаются слепки и пишутся
 * события. Страховочно то же делает app()->terminating() (см. AppServiceProvider).
 */
class FlushEntityLog
{
    /**
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        EntityLogService::flush();

        return $response;
    }
}
