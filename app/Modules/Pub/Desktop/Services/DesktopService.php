<?php

namespace App\Modules\Pub\Desktop\Services;

use App\Modules\Pub\Desktop\Models\Desktop;
use App\Modules\Pub\Desktop\Models\DesktopWidget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Рабочие столы (patch v30): столы пользователя и системные пресеты, раскладка
 * виджетов, контекст (валюта и период).
 *
 * Права: личный стол видит и меняет только владелец; системный пресет видят все,
 * меняет только админ (User::isPanelAdmin()). Выбор валюты и периода — в сессии
 * (desktop_context.{id}), поэтому менять его можно и на системном пресете.
 */
class DesktopService
{
    /** Максимум виджетов на одном столе */
    public const MAX_WIDGETS = 200;

    /** Максимальная высота блока в ячейках (свободный размер) */
    public const MAX_ROWS = 64;

    /** Название пустого стола, который создаётся пользователю без столов */
    public const DEFAULT_NAME = 'Мой рабочий стол';

    /** Допустимый uid виджета (его выдаёт браузер) */
    public const UID_PATTERN = '/^[A-Za-z0-9_-]{1,32}$/';

    /** Ключ сессии с выбором валюты и периода: desktop_context.{id} */
    public const SESSION_KEY = 'desktop_context';

    /*** СПИСКИ ***/

    /**
     * Личные столы пользователя
     *
     * @param User $user
     * @return Collection<Desktop>
     */
    public static function forUser(User $user): Collection
    {
        return Desktop::ownedBy($user)->orderBy('sort')->orderBy('id')->get();
    }

    /**
     * Системные пресеты
     *
     * @return Collection<Desktop>
     */
    public static function systems(): Collection
    {
        return Desktop::system()->orderBy('sort')->orderBy('id')->get();
    }

    /**
     * Стол, который открывается с домика и после входа.
     *
     * Личный с is_default; нет — первый личный (получает флаг); личных нет —
     * копия системного пресета по умолчанию; нет и его — пустой стол.
     *
     * @param User $user
     * @return Desktop
     */
    public static function home(User $user): Desktop
    {
        $desktop = Desktop::ownedBy($user)->where('is_default', true)->orderBy('sort')->orderBy('id')->first();
        if ($desktop) {
            return $desktop;
        }

        $desktop = static::forUser($user)->first();
        if ($desktop) {
            $desktop->update(['is_default' => true]);

            return $desktop;
        }

        return DB::transaction(function () use ($user) {
            $preset = Desktop::system()->where('is_default', true)->orderBy('sort')->orderBy('id')->first();

            $desktop = $preset
                ? static::copy($preset, $user)
                : static::create($user, static::DEFAULT_NAME);

            $desktop->update(['is_default' => true]);

            return $desktop;
        });
    }

    /*** СТОЛЫ ***/

    /**
     * Создать пустой стол
     *
     * @param User $user
     * @param string $name
     * @param bool $system системный пресет (только админ)
     * @return Desktop
     * @throws ValidationException
     */
    public static function create(User $user, string $name, bool $system = false): Desktop
    {
        $name = static::cleanName($name);

        if ($system && !$user->isPanelAdmin()) {
            throw ValidationException::withMessages(['system' => 'Системные пресеты создаёт только администратор']);
        }

        $group = $system ? Desktop::system() : Desktop::ownedBy($user);

        return Desktop::create([
            'user_id' => $system ? null : $user->id,
            'name' => $name,
            'is_system' => $system,
            'is_default' => false,
            'sort' => (int) $group->max('sort') + 1,
            'version' => 1,
            'created_by' => $user->id,
            'updated_by' => $user->id,
        ]);
    }

    /**
     * Скопировать стол вместе с виджетами.
     *
     * uid виджетов сохраняются; виджеты, которых нет в коде или которые недоступны
     * пользователю, пропускаются (место остаётся пустым). Копия системного пресета
     * помнит источник и его версию — для предложения обновиться.
     *
     * @param Desktop $source
     * @param User $user владелец копии
     * @param string|null $name null — «{имя} (копия)», у личной копии пресета — имя пресета
     * @param bool $system копия — системный пресет (только админ)
     * @return Desktop
     * @throws AuthorizationException|ValidationException
     */
    public static function copy(Desktop $source, User $user, ?string $name = null, bool $system = false): Desktop
    {
        static::assertView($source, $user);

        if ($name === null || trim($name) === '') {
            $name = $source->is_system && !$system ? $source->name : $source->name . ' (копия)';
        }

        return DB::transaction(function () use ($source, $user, $name, $system) {
            $desktop = static::create($user, $name, $system);

            $desktop->context = $source->context;
            if ($source->is_system) {
                $desktop->source_id = $source->id;
                $desktop->source_version = $source->version;
            }
            $desktop->save();

            foreach ($source->widgets()->get() as $item) {
                if (!WidgetRegistry::availableFor($item->widget, $user)) continue;

                DesktopWidget::create(['desktop_id' => $desktop->id] + $item->only(['uid', 'widget', 'x', 'y', 'w', 'h', 'free_size', 'settings']));
            }

            return $desktop;
        });
    }

    /**
     * Переименовать стол
     *
     * @param Desktop $desktop
     * @param string $name
     * @param User $user
     * @return Desktop
     * @throws AuthorizationException|ValidationException
     */
    public static function rename(Desktop $desktop, string $name, User $user): Desktop
    {
        static::assertEdit($desktop, $user);

        $desktop->update(['name' => static::cleanName($name), 'updated_by' => $user->id]);

        return $desktop;
    }

    /**
     * Удалить стол вместе с виджетами. Последний личный стол удалить нельзя;
     * флаг «по умолчанию» переходит первому оставшемуся столу той же группы
     *
     * @param Desktop $desktop
     * @param User $user
     * @return void
     * @throws AuthorizationException|ValidationException
     */
    public static function delete(Desktop $desktop, User $user): void
    {
        static::assertEdit($desktop, $user);

        DB::transaction(function () use ($desktop) {
            $group = static::group($desktop)->where('id', '!=', $desktop->id)->orderBy('sort')->orderBy('id');

            if (!$desktop->is_system && !(clone $group)->exists()) {
                throw ValidationException::withMessages(['desktop' => 'Нельзя удалить единственный рабочий стол']);
            }

            DesktopWidget::where('desktop_id', $desktop->id)->delete();
            $desktop->delete();

            if ($desktop->is_default && ($next = $group->first())) {
                $next->update(['is_default' => true]);
            }
        });

        session()->forget(static::sessionKey($desktop));
    }

    /**
     * Сделать стол столом по умолчанию: личный — открывается с домика, системный —
     * копируется новым пользователям (только админ)
     *
     * @param Desktop $desktop
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public static function makeDefault(Desktop $desktop, User $user): void
    {
        static::assertEdit($desktop, $user);

        DB::transaction(function () use ($desktop, $user) {
            static::group($desktop)->where('id', '!=', $desktop->id)->where('is_default', true)
                ->update(['is_default' => false]);

            $desktop->update(['is_default' => true, 'updated_by' => $user->id]);
        });
    }

    /*** РАСКЛАДКА ***/

    /**
     * Сохранить раскладку целиком: виджеты, которых нет в items, удаляются,
     * остальные создаются или обновляются по uid. У системного пресета растёт version.
     *
     * @param Desktop $desktop
     * @param array $items [['uid', 'widget', 'x', 'y', 'w', 'h', 'free_size', 'settings'], …]
     * @param User $user
     * @param array|null $context ['currency', 'period'] — умолчания стола; null — не менять
     * @return Desktop
     * @throws AuthorizationException|ValidationException
     */
    public static function save(Desktop $desktop, array $items, User $user, ?array $context = null): Desktop
    {
        static::assertEdit($desktop, $user);

        $rows = static::cleanItems($items, $user);

        if (count($rows) > static::MAX_WIDGETS) {
            throw ValidationException::withMessages([
                'items' => 'На столе может быть не больше ' . static::MAX_WIDGETS . ' виджетов',
            ]);
        }

        DB::transaction(function () use ($desktop, $rows, $user, $context) {
            DesktopWidget::where('desktop_id', $desktop->id)->whereNotIn('uid', array_keys($rows))->delete();

            $existing = DesktopWidget::where('desktop_id', $desktop->id)->get()->keyBy('uid');

            foreach ($rows as $uid => $row) {
                $model = $existing->get($uid) ?? new DesktopWidget(['desktop_id' => $desktop->id, 'uid' => $uid]);
                $model->fill($row)->save();
            }

            if ($context !== null) {
                $desktop->context = DesktopContext::make($context, $user)->toArray();
            }

            if ($desktop->is_system) {
                $desktop->version = (int) $desktop->version + 1;
            }

            $desktop->updated_by = $user->id;
            $desktop->save();
        });

        return $desktop->refresh();
    }

    /**
     * Проверить элементы раскладки: плохой uid, дубль uid, неизвестный или недоступный
     * виджет — элемент отбрасывается; размер — ближайший разрешённый; позиция — в сетке
     *
     * @param array $items
     * @param User $user
     * @return array uid => ['widget', 'x', 'y', 'w', 'h', 'free_size', 'settings']
     */
    protected static function cleanItems(array $items, User $user): array
    {
        $columns = (int) config('desktop.columns', 32);
        $rows = [];
        $seen = [];

        foreach ($items as $item) {
            if (!is_array($item)) continue;

            $uid = is_scalar($item['uid'] ?? null) ? (string) $item['uid'] : '';
            if (!preg_match(static::UID_PATTERN, $uid) || isset($seen[$uid])) continue;
            $seen[$uid] = true;

            $id = is_string($item['widget'] ?? null) ? $item['widget'] : null;
            if (!WidgetRegistry::availableFor($id, $user)) continue;

            $class = WidgetRegistry::find($id);

            // на блоке отжат замок — размер оставляем как есть, иначе берём ближайший разрешённый
            $free = !empty($item['free_size']);
            [$w, $h] = [static::int($item['w'] ?? 0), static::int($item['h'] ?? 0)];
            if (!$free && !$class::allows($w, $h)) {
                [$w, $h] = $class::nearestSize($w, $h);
            }
            $w = max(1, min($columns, $w));
            $h = max(1, min(static::MAX_ROWS, $h));

            $settings = $item['settings'] ?? [];
            if (is_string($settings)) {
                $settings = json_decode($settings, true);
            }

            $rows[$uid] = [
                'widget' => $id,
                'x' => max(0, min($columns - $w, static::int($item['x'] ?? 0))),
                // колонка y — unsigned smallint
                'y' => max(0, min(60000, static::int($item['y'] ?? 0))),
                'w' => $w,
                'h' => $h,
                'free_size' => $free,
                'settings' => $class::normalize(is_array($settings) ? $settings : []),
            ];
        }

        return $rows;
    }

    /*** КОНТЕКСТ ***/

    /**
     * Контекст стола: умолчания портала ← умолчания стола ← выбор в сессии
     *
     * @param Desktop $desktop
     * @param User $user
     * @return DesktopContext
     */
    public static function context(Desktop $desktop, User $user): DesktopContext
    {
        $context = DesktopContext::DEFAULTS;

        foreach ([(array) $desktop->context, (array) session(static::sessionKey($desktop), [])] as $layer) {
            foreach (array_keys(DesktopContext::DEFAULTS) as $key) {
                if (is_string($layer[$key] ?? null) && trim($layer[$key]) !== '') {
                    $context[$key] = $layer[$key];
                }
            }
        }

        return DesktopContext::make($context, $user);
    }

    /**
     * Выбрать валюту и период стола: сохраняется в сессии, неизвестные значения игнорируются
     *
     * @param Desktop $desktop
     * @param array $input ['currency' => 'USD', 'period' => 'last30']
     * @param User $user
     * @return DesktopContext
     * @throws AuthorizationException
     */
    public static function setContext(Desktop $desktop, array $input, User $user): DesktopContext
    {
        static::assertView($desktop, $user);

        $current = static::context($desktop, $user)->toArray();
        $input = array_intersect_key($input, DesktopContext::DEFAULTS);

        if (DesktopContext::validCurrency(is_string($input['currency'] ?? null) ? $input['currency'] : null) === null) {
            unset($input['currency']);
        }
        if (!array_key_exists((string) ($input['period'] ?? ''), DesktopContext::PERIODS)) {
            unset($input['period']);
        }

        $context = DesktopContext::make($input + $current, $user);
        session()->put(static::sessionKey($desktop), $context->toArray());

        return $context;
    }

    /*** ОТРИСОВКА ***/

    /**
     * HTML виджета стола; недоступный или удалённый из кода — плашка
     *
     * @param Desktop $desktop
     * @param string $widget_id
     * @param int $w
     * @param int $h
     * @param array $settings сырые настройки (нормализует виджет)
     * @param User $user
     * @param bool $fresh сбросить кэш данных
     * @param bool $free размер задан вручную — к размерам виджета не приводится
     * @param array $view состояние просмотра блока (листаемый месяц и т.п.), отбирает сам виджет
     * @return string
     * @throws AuthorizationException
     */
    public static function render(Desktop $desktop, string $widget_id, int $w, int $h, array $settings, User $user, bool $fresh = false, bool $free = false, array $view = []): string
    {
        static::assertView($desktop, $user);

        if (!WidgetRegistry::availableFor($widget_id, $user)) {
            return '<div class="desk-widget"><div class="desk-widget-body"><div class="desk-empty">'
                . '<i class="fa-light fa-lock"></i> Виджет недоступен</div></div></div>';
        }

        $class = WidgetRegistry::find($widget_id);
        if (!$free && !$class::allows($w, $h)) {
            [$w, $h] = $class::nearestSize($w, $h);
        }

        return WidgetRegistry::instance($widget_id)->html($w, $h, $settings, static::context($desktop, $user), false, $fresh, $view);
    }

    /**
     * HTML нескольких виджетов стола за один запрос. Ошибка одного виджета не роняет
     * остальные: на его месте плашка «Не удалось загрузить виджет» с кнопкой «Повторить»
     *
     * @param Desktop $desktop
     * @param array $items [['uid', 'widget', 'w', 'h', 'free_size', 'settings', 'view'], …]; плохой или повторный uid пропускается
     * @param User $user
     * @param bool $fresh сбросить кэш данных
     * @return array uid => html
     * @throws AuthorizationException
     */
    public static function renderBatch(Desktop $desktop, array $items, User $user, bool $fresh = false): array
    {
        static::assertView($desktop, $user);

        $html = [];
        foreach ($items as $item) {
            if (!is_array($item)) continue;

            $uid = is_scalar($item['uid'] ?? null) ? (string) $item['uid'] : '';
            if (!preg_match(static::UID_PATTERN, $uid) || isset($html[$uid])) continue;

            $settings = $item['settings'] ?? [];
            if (is_string($settings)) {
                $settings = json_decode($settings, true);
            }
            $view = $item['view'] ?? [];
            if (is_string($view)) {
                $view = json_decode($view, true);
            }

            try {
                $html[$uid] = static::render(
                    $desktop,
                    is_string($item['widget'] ?? null) ? $item['widget'] : '',
                    static::int($item['w'] ?? 0),
                    static::int($item['h'] ?? 0),
                    is_array($settings) ? $settings : [],
                    $user,
                    $fresh,
                    !empty($item['free_size']),
                    is_array($view) ? $view : []
                );
            } catch (\Throwable $e) {
                report($e);
                $html[$uid] = static::errorHtml();
            }
        }

        return $html;
    }

    /**
     * Плашка ошибки отрисовки виджета (та же, что errorHtml() в osmo-desktop.js)
     *
     * @return string
     */
    protected static function errorHtml(): string
    {
        return '<div class="desk-widget desk-load-error"><div class="desk-widget-body"><div class="desk-error">'
            . '<span><i class="fa-light fa-triangle-exclamation me-1"></i>Не удалось загрузить виджет</span>'
            . '<button type="button" class="btn btn-sm btn-light" data-desk-tool="retry">Повторить</button>'
            . '</div></div></div>';
    }

    /*** ОБНОВЛЕНИЕ ИЗ ПРЕСЕТА ***/

    /**
     * Обновить личный стол из системного пресета, из которого он скопирован: раскладка
     * и умолчания валюты и периода заменяются текущей версией пресета (недоступные
     * пользователю виджеты пропускаются, как в copy()), выбор в сессии сбрасывается
     *
     * @param Desktop $desktop
     * @param User $user
     * @return Desktop
     * @throws AuthorizationException|ValidationException
     */
    public static function applySource(Desktop $desktop, User $user): Desktop
    {
        static::assertEdit($desktop, $user);

        if ($desktop->is_system || !$desktop->source_id) {
            throw ValidationException::withMessages([
                'desktop' => 'Этот стол создан не из системного пресета — обновлять его не из чего',
            ]);
        }

        $source = Desktop::system()->find($desktop->source_id);
        if (!$source) {
            throw ValidationException::withMessages([
                'desktop' => 'Системный пресет, из которого создан стол, удалён — обновить стол нельзя',
            ]);
        }

        DB::transaction(function () use ($desktop, $source, $user) {
            DesktopWidget::where('desktop_id', $desktop->id)->delete();

            foreach ($source->widgets()->get() as $item) {
                if (!WidgetRegistry::availableFor($item->widget, $user)) continue;

                DesktopWidget::create(['desktop_id' => $desktop->id] + $item->only(['uid', 'widget', 'x', 'y', 'w', 'h', 'free_size', 'settings']));
            }

            $desktop->context = $source->context;
            $desktop->source_version = $source->version;
            $desktop->updated_by = $user->id;
            $desktop->save();
        });

        session()->forget(static::sessionKey($desktop));

        return $desktop->refresh();
    }

    /**
     * Виджеты стола для страницы: позиция, настройки, описание виджета и доступность
     *
     * @param Desktop $desktop
     * @param User $user
     * @return array [['uid', 'widget', 'x', 'y', 'w', 'h', 'free_size', 'settings', 'meta' => array|null, 'available' => bool], …]
     */
    public static function gridItems(Desktop $desktop, User $user): array
    {
        return $desktop->widgets()->get()->map(function (DesktopWidget $item) use ($user) {
            $class = $item->widgetClass();

            return $item->toGrid() + [
                'meta' => $class ? $class::meta() : null,
                'available' => $class !== null && $class::available($user),
            ];
        })->values()->all();
    }

    /**
     * Сводка стола для страницы и JS
     *
     * @param Desktop $desktop
     * @param User $user
     * @return array
     */
    public static function summary(Desktop $desktop, User $user): array
    {
        $source = !$desktop->is_system && $desktop->source_id ? $desktop->source : null;

        return [
            'id' => $desktop->id,
            'name' => $desktop->name,
            'is_system' => (bool) $desktop->is_system,
            'is_default' => (bool) $desktop->is_default,
            'can_edit' => $desktop->canEdit($user),
            'version' => (int) $desktop->version,
            'source_id' => $desktop->source_id,
            'source_version' => $desktop->source_version,
            // пресет, из которого скопирован стол, с тех пор менялся
            'update_available' => $source !== null && $source->is_system
                && (int) $source->version > (int) $desktop->source_version,
        ];
    }

    /*** ДОСТУП ***/

    /**
     * Проверить право открыть стол
     *
     * @param Desktop $desktop
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public static function assertView(Desktop $desktop, User $user): void
    {
        if (!$desktop->canView($user)) {
            throw new AuthorizationException('Этот стол доступен только его владельцу');
        }
    }

    /**
     * Проверить право менять стол
     *
     * @param Desktop $desktop
     * @param User $user
     * @return void
     * @throws AuthorizationException
     */
    public static function assertEdit(Desktop $desktop, User $user): void
    {
        if ($desktop->canEdit($user)) {
            return;
        }

        throw new AuthorizationException($desktop->is_system
            ? 'Системные пресеты меняет только администратор'
            : 'Этот стол может менять только его владелец');
    }

    /*** ПОМОЩНИКИ ***/

    /**
     * Столы той же группы: личные владельца или системные
     *
     * @param Desktop $desktop
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected static function group(Desktop $desktop)
    {
        return $desktop->is_system ? Desktop::system() : Desktop::ownedBy((int) $desktop->user_id);
    }

    /**
     * Название стола: без пробелов по краям, не пустое, до 100 символов
     *
     * @param string $name
     * @return string
     * @throws ValidationException
     */
    protected static function cleanName(string $name): string
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name));

        if ($name === '') {
            throw ValidationException::withMessages(['name' => 'Укажите название стола']);
        }

        return mb_substr($name, 0, 100);
    }

    /**
     * Ключ сессии с выбором валюты и периода
     *
     * @param Desktop $desktop
     * @return string
     */
    protected static function sessionKey(Desktop $desktop): string
    {
        return static::SESSION_KEY . '.' . $desktop->id;
    }

    /**
     * Целое из входа браузера (число или числовая строка), иначе 0
     *
     * @param mixed $value
     * @return int
     */
    protected static function int($value): int
    {
        return is_numeric($value) ? (int) round((float) $value) : 0;
    }
}
