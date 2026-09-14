<?php

namespace App\Modules\Pub\Desktop\Services;

use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;

/**
 * Реестр виджетов рабочего стола (patch v30).
 *
 * Виджеты находятся сами: классы app/Modules/Pub/Desktop/Widgets/{Категория}/*Widget.php,
 * наследники Widget. Добавить виджет = положить класс и его вьюху
 * themes/metronic/pub/desktop/widgets/{id}.blade.php — правки общих файлов не нужны.
 */
class WidgetRegistry
{
    /** id => класс, в порядке категорий, order(), name() */
    protected static ?array $classes = null;

    /**
     * Все виджеты: id => класс
     *
     * @return array<string, class-string<Widget>>
     */
    public static function all(): array
    {
        if (static::$classes !== null) {
            return static::$classes;
        }

        $root = str_replace('\\', '/', app_path()) . '/';
        $found = [];

        foreach (glob(app_path('Modules/Pub/Desktop/Widgets/*/*Widget.php')) ?: [] as $file) {
            $relative = substr(str_replace('\\', '/', $file), strlen($root));
            $class = 'App\\' . str_replace(['/', '.php'], ['\\', ''], $relative);

            if (!class_exists($class) || !is_subclass_of($class, Widget::class)) continue;
            if ((new \ReflectionClass($class))->isAbstract()) continue;

            $found[$class::id()] = $class;
        }

        $categories = array_keys(static::categories());
        $position = function (string $class) use ($categories) {
            $index = array_search($class::category(), $categories, true);

            return [$index === false ? 999 : $index, $class::order(), mb_strtolower($class::name())];
        };

        uasort($found, fn($a, $b) => $position($a) <=> $position($b));

        return static::$classes = $found;
    }

    /**
     * Класс виджета по id
     *
     * @param string|null $id
     * @return class-string<Widget>|null
     */
    public static function find(?string $id): ?string
    {
        return $id === null ? null : (static::all()[$id] ?? null);
    }

    /**
     * Экземпляр виджета по id
     *
     * @param string|null $id
     * @return Widget|null
     */
    public static function instance(?string $id): ?Widget
    {
        $class = static::find($id);

        return $class ? app($class) : null;
    }

    /**
     * Виджет существует и доступен пользователю
     *
     * @param string|null $id
     * @param User $user
     * @return bool
     */
    public static function availableFor(?string $id, User $user): bool
    {
        $class = static::find($id);

        return $class !== null && $class::available($user);
    }

    /**
     * Категории библиотеки из config/desktop.php: код => ['name', 'icon', 'description']
     *
     * @return array
     */
    public static function categories(): array
    {
        return (array) config('desktop.categories', []);
    }

    /**
     * Библиотека для пользователя: только доступные виджеты, пустые категории пропущены
     *
     * @param User $user
     * @return array код категории => ['key', 'name', 'icon', 'description', 'widgets' => [Widget::meta(), …]]
     */
    public static function forUser(User $user): array
    {
        $out = [];

        foreach (static::categories() as $key => $category) {
            $out[$key] = $category + ['key' => $key, 'widgets' => []];
        }

        foreach (static::all() as $class) {
            if (!$class::available($user)) continue;

            $key = $class::category();
            if (!isset($out[$key])) continue;

            $out[$key]['widgets'][] = $class::meta();
        }

        return array_filter($out, fn($category) => !empty($category['widgets']));
    }
}
