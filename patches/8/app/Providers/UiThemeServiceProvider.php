<?php

namespace App\Providers;

use App\Support\UiTheme;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Тема оформления: маршрут переключения и признак «страница переведена».
 */
class UiThemeServiceProvider extends ServiceProvider
{
    /** Признак выставляется один раз — первой отрисованной страницей */
    protected static bool $nativeResolved = false;

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ui.php', 'ui');
    }

    public function boot(): void
    {
        Route::middleware('web')->group(function () {
            Route::get('/ui-theme/{theme}', function (string $theme) {
                if (! UiTheme::exists($theme) || ! UiTheme::switchVisible()) {
                    return redirect()->back();
                }

                $back = url()->previous() ?: '/';

                return redirect()->to($back)->withCookie(UiTheme::cookie($theme));
            })->name('ui.theme');
        });

        $this->shareNativeFlag();
    }

    /**
     * Определяет, переведена ли текущая страница на новую тему.
     *
     * Нужно из-за конфликта шкал размеров шрифта: MaterialPro и Metronic
     * определяют одни и те же классы .fs-1…fs-9 разными значениями.
     * Переведённые страницы получают класс theme-metronic на body, и только
     * для них включается шкала Metronic (см. osmo-compat.css). Непереведённые
     * продолжают жить со шкалой MaterialPro из /css/app.css.
     *
     * Blade рендерит дочернюю вьюху раньше layout, поэтому к моменту вывода
     * тега body признак уже известен.
     *
     * @return void
     */
    protected function shareNativeFlag(): void
    {
        View::share('ui_theme_native', false);

        View::composer('*', function ($view) {
            if (static::$nativeResolved) return;

            // layout и компоненты живут в теме всегда — по ним судить нельзя
            if (Str::startsWith($view->getName(), ['layouts.', 'components.'])) return;

            $themePath = UiTheme::viewsPath();
            if (empty($themePath)) {
                static::$nativeResolved = true;
                return;
            }

            static::$nativeResolved = true;

            $isNative = Str::startsWith(
                str_replace('\\', '/', (string) $view->getPath()),
                str_replace('\\', '/', $themePath)
            );

            View::share('ui_theme_native', $isNative);
        });
    }
}
