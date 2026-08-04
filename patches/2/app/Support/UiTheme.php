<?php

namespace App\Support;

use Illuminate\Support\Facades\Cookie;

/**
 * Реестр тем оформления.
 *
 * Активная тема выставляется в App\Http\Middleware\ResolveUiTheme
 * и дальше доступна из любого места: UiTheme::current().
 */
class UiTheme
{
    protected static ?string $current = null;

    /** Код активной темы */
    public static function current(): string
    {
        return static::$current ?? static::fallback();
    }

    /** Явно задать активную тему на текущий запрос */
    public static function use(string $theme): void
    {
        static::$current = static::exists($theme) ? $theme : static::fallback();
    }

    public static function is(string $theme): bool
    {
        return static::current() === $theme;
    }

    public static function fallback(): string
    {
        $default = (string) config('ui.default', 'materialpro');

        return static::exists($default) ? $default : (string) array_key_first(static::all());
    }

    /** @return array<string, array> */
    public static function all(): array
    {
        return (array) config('ui.themes', []);
    }

    public static function exists(?string $theme): bool
    {
        return $theme !== null && array_key_exists($theme, static::all());
    }

    /** Настройки темы: UiTheme::config('assets') */
    public static function config(string $key = null, $default = null)
    {
        $theme = static::all()[static::current()] ?? [];

        if ($key === null) {
            return $theme;
        }

        return data_get($theme, $key, $default);
    }

    /** Абсолютный путь к каталогу вьюх темы или null для базовой темы */
    public static function viewsPath(string $theme = null): ?string
    {
        $views = data_get(static::all(), ($theme ?? static::current()) . '.views');

        return $views ? resource_path('views/' . trim($views, '/')) : null;
    }

    /** Ссылка на ассет активной темы: UiTheme::asset('css/style.bundle.css') */
    public static function asset(string $path): string
    {
        $base = rtrim((string) static::config('assets', ''), '/');

        return $base . '/' . ltrim($path, '/');
    }

    public static function cookieName(): string
    {
        return (string) config('ui.cookie', 'ui_theme');
    }

    /** Cookie для ответа с выбранной темой */
    public static function cookie(string $theme)
    {
        return Cookie::make(
            static::cookieName(),
            $theme,
            (int) config('ui.cookie_lifetime', 525600),
            '/',
            null,
            null,
            false // доступна из JS: нужна для мгновенного переключения без перезагрузки конфигов
        );
    }

    /** Показывать ли переключатель текущему пользователю */
    public static function switchVisible(): bool
    {
        if (! config('ui.switch_enabled', true)) {
            return false;
        }

        if (config('ui.switch_admin_only', false)) {
            $user = auth()->user();

            return $user && method_exists($user, 'isAdmin') && $user->isAdmin();
        }

        return true;
    }
}
