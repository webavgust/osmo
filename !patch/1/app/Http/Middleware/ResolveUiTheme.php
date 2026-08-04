<?php

namespace App\Http\Middleware;

use App\Support\UiTheme;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

/**
 * Определяет активную тему и подкладывает её каталог вьюх первым в поиске.
 *
 * Blade ищет шаблон по списку путей и берёт первый найденный, поэтому
 * resources/views/themes/metronic/pub/order/index.blade.php перекроет
 * resources/views/pub/order/index.blade.php, а всё, чего в теме нет,
 * отрисуется старой вёрсткой. Так же работают и x-компоненты.
 */
class ResolveUiTheme
{
    public function handle(Request $request, Closure $next)
    {
        $theme = $request->cookie(UiTheme::cookieName());

        if (! UiTheme::exists($theme)) {
            $theme = UiTheme::fallback();
        }

        UiTheme::use($theme);

        $finder = View::getFinder();

        if ($path = UiTheme::viewsPath()) {
            if (is_dir($path)) {
                $finder->prependLocation($path);
            }
        }

        // сбрасываем кэш уже найденных имён вьюх — пути изменились
        $finder->flush();

        View::share('ui_theme', $theme);

        return $next($request);
    }
}
