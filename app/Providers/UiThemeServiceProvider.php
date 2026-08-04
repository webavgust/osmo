<?php

namespace App\Providers;

use App\Support\UiTheme;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

/**
 * Регистрирует маршрут переключения оформления.
 *
 * GET /ui-theme/{theme}  ->  ставит cookie и возвращает на ту же страницу.
 * Роут объявлен здесь, чтобы не трогать routes/web.php.
 */
class UiThemeServiceProvider extends ServiceProvider
{
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
    }
}
