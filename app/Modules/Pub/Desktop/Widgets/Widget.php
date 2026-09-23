<?php

namespace App\Modules\Pub\Desktop\Widgets;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Виджет рабочего стола (patch v30) — базовый класс и контракт.
 *
 * Чтобы добавить виджет:
 *  1. класс app/Modules/Pub/Desktop/Widgets/{Категория}/{Имя}Widget.php (наследник Widget);
 *  2. вьюха тела resources/views/themes/metronic/pub/desktop/widgets/{id}.blade.php.
 * Реестр (WidgetRegistry) найдёт виджет сам.
 *
 * Размеры — строки 'ШxВ' в ячейках сетки на 32 колонки: ['4x2', '8x4']. Менять размер
 * можно только между ними. Вьюха получает $w и $h и перестраивает содержимое под размер.
 *
 * Настройки — схема fields(): ['key', 'type', 'label', 'default', 'options', 'hint',
 * 'required', 'min', 'max', 'entities']. Типы: text, textarea, number, select, bool,
 * currency, period, entity (['type' => 'proposal', 'id' => '…'], entities — какие типы
 * можно выбрать: proposal, partner, company, deal), list (массив). Общие настройки
 * (заголовок, заливка, шрифт, а при usesCurrency()/usesPeriod() — валюта и период)
 * добавляются сами. Форма настроек обязана присылать bool явно (0/1): отсутствующий
 * ключ означает «по умолчанию».
 *
 * Данные — data(): только массивы и скаляры (кладутся в кэш на ttl() секунд).
 * sample() — образцовые данные для превью в библиотеке (по умолчанию живые).
 * Вьюха тела получает: $widget (класс), $w, $h, $size ('4x2'), $settings, $data,
 * $ctx (DesktopContext), $preview, а также $dw / $dh (ступень ширины и высоты блока:
 * xs, sm, md, lg, xl) и $rows (сколько строк списка влезет). Размер блока произвольный —
 * пользователь может отжать замок размеров, поэтому вьюха не проверяет $size на равенство,
 * а смотрит на $dw / $dh и обрезает списки по $rows. Оболочку (заголовок, заливка, шрифт) рисует
 * pub.desktop.partials.widget, стили — public/metronic/css/osmo-desktop.css.
 *
 * Виджеты, меняющие контекст стола (валюта, период), помечают элемент
 * data-desk-context="currency|period": страница отправит значение и перерисует
 * виджеты с usesCurrency()/usesPeriod().
 */
abstract class Widget
{
    /** Заливки блока (цвета Metronic: primary — синий, info — фиолетовый) */
    public const FILLS = [
        'none' => 'Без заливки',
        'light' => 'Серая',
        'light-primary' => 'Светло-синяя',
        'light-info' => 'Светло-фиолетовая',
        'light-success' => 'Светло-зелёная',
        'light-warning' => 'Светло-жёлтая',
        'light-danger' => 'Светло-красная',
        'primary' => 'Синяя',
        'info' => 'Фиолетовая',
        'success' => 'Зелёная',
        'dark' => 'Тёмная',
    ];

    /** Расчётная сторона ячейки сетки, px (на 1600–1920 px ячейка 40–50 px) */
    public const CELL = 40;

    /** Расчётная высота строки списка или таблицы, px */
    public const ROW = 26;

    /** Сторона ячейки на самом широком экране, px: строк с запасом для подгона .desk-fit */
    public const CELL_MAX = 64;

    /** Ступени ширины блока в ячейках: до скольких колонок держится ступень */
    public const W_STEPS = ['xs' => 2, 'sm' => 5, 'md' => 11, 'lg' => 23];

    /** Ступени высоты блока в ячейках */
    public const H_STEPS = ['xs' => 1, 'sm' => 2, 'md' => 5, 'lg' => 9];

    /** Размер шрифта: множитель --desk-font-scale в CSS */
    public const FONTS = [
        'auto' => 'Авто',
        's' => 'Мелкий',
        'm' => 'Средний',
        'l' => 'Крупный',
        'xl' => 'Очень крупный',
    ];

    /*** ОПИСАНИЕ ***/

    /** Код виджета: латиница, цифры, _ (он же имя вьюхи) */
    abstract public static function id(): string;

    /** Название в библиотеке */
    abstract public static function name(): string;

    /** Код категории из config/desktop.php */
    abstract public static function category(): string;

    /** Размеры 'ШxВ' */
    abstract public static function sizes(): array;

    /**
     * Данные виджета
     *
     * @param array $settings нормализованные настройки
     * @param DesktopContext $ctx
     * @return array
     */
    abstract public function data(array $settings, DesktopContext $ctx): array;

    /** Короткое описание в библиотеке */
    public static function description(): string
    {
        return '';
    }

    /** Иконка Font Awesome без префикса стиля: fa-chart-line */
    public static function icon(): string
    {
        return 'fa-cube';
    }

    /** Размер по умолчанию при добавлении */
    public static function defaultSize(): string
    {
        return static::sizes()[0];
    }

    /** Порядок внутри категории */
    public static function order(): int
    {
        return 500;
    }

    /** Собственные настройки виджета (см. docblock класса) */
    public static function fields(): array
    {
        return [];
    }

    /** Денежный виджет: добавить настройку валюты */
    public static function usesCurrency(): bool
    {
        return false;
    }

    /** Виджет с периодом: добавить настройку периода */
    public static function usesPeriod(): bool
    {
        return false;
    }

    /** Показывать заголовок по умолчанию */
    public static function showTitle(): bool
    {
        return true;
    }

    /** Блок в рамке с фоном; false — прозрачный без рамки (заголовок ряда, разделитель) */
    public static function framed(): bool
    {
        return true;
    }

    /** Доступен ли пользователю (права страницы-источника) */
    public static function available(User $user): bool
    {
        return true;
    }

    /** Кэш данных, секунд; 0 — без кэша */
    public static function ttl(): int
    {
        return 300;
    }

    /** Вьюха тела */
    public static function view(): string
    {
        return 'pub.desktop.widgets.' . static::id();
    }

    /**
     * Страница-источник для ссылки в заголовке
     *
     * @param array $settings
     * @return string|null
     */
    public static function sourceUrl(array $settings): ?string
    {
        return null;
    }

    /**
     * Настройки для превью в библиотеке (поверх значений по умолчанию)
     *
     * @return array
     */
    public static function previewSettings(): array
    {
        return [];
    }

    /**
     * Образцовые данные для превью в библиотеке
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        return $this->data($settings, $ctx);
    }

    /**
     * Состояние просмотра блока (листаемый месяц, раскрытый день): живёт в браузере до
     * перезагрузки страницы, в настройках не хранится. Приходит от клика по
     * [data-desk-view] и уходит в data() / sample() третьим аргументом и во вьюху как $view.
     * Виджет сам отбирает допустимые ключи; по умолчанию состояния нет
     *
     * @param array $input сырое состояние из браузера
     * @return array
     */
    public static function viewState(array $input): array
    {
        return [];
    }

    /*** НАСТРОЙКИ ***/

    /**
     * Полная схема настроек: свои поля (group = data) + общие (group = view)
     *
     * @return array
     */
    public static function schema(): array
    {
        $fields = array_map(fn($field) => $field + ['group' => 'data'], static::fields());

        if (static::usesCurrency()) {
            $fields[] = [
                'key' => 'currency', 'type' => 'currency', 'label' => 'Валюта', 'group' => 'data',
                'default' => DesktopContext::FROM_DESK, 'hint' => '«Со стола» — как в виджете «Выбор валюты»',
            ];
        }

        if (static::usesPeriod()) {
            $fields[] = [
                'key' => 'period', 'type' => 'period', 'label' => 'Период', 'group' => 'data',
                'default' => DesktopContext::FROM_DESK, 'hint' => '«Со стола» — как в виджете «Период»',
            ];
        }

        return array_merge($fields, [
            ['key' => 'title', 'type' => 'text', 'label' => 'Заголовок', 'group' => 'view', 'default' => static::name(), 'max' => 100],
            ['key' => 'show_title', 'type' => 'bool', 'label' => 'Показывать заголовок', 'group' => 'view', 'default' => static::showTitle()],
            ['key' => 'fill', 'type' => 'select', 'label' => 'Заливка', 'group' => 'view', 'default' => 'none', 'options' => static::FILLS],
            ['key' => 'font', 'type' => 'select', 'label' => 'Размер шрифта', 'group' => 'view', 'default' => 'auto', 'options' => static::FONTS],
        ]);
    }

    /**
     * Варианты выбора поля: select — options (массив или callable), currency — «со стола» +
     * валюты портала, period — «со стола» + периоды
     *
     * @param array $field
     * @return array значение => подпись
     */
    public static function options(array $field): array
    {
        return match ($field['type']) {
            'currency' => [DesktopContext::FROM_DESK => 'Со стола'] + collect(DesktopContext::currencies())
                ->mapWithKeys(fn($currency, $slug) => [$slug => $currency->name . ' (' . $currency->symbol . ')'])
                ->all(),
            'period' => [DesktopContext::FROM_DESK => 'Со стола'] + DesktopContext::PERIODS,
            default => is_callable($field['options'] ?? null) ? (array) call_user_func($field['options']) : (array) ($field['options'] ?? []),
        };
    }

    /**
     * Значения по умолчанию
     *
     * @return array
     */
    public static function defaults(): array
    {
        return static::normalize([]);
    }

    /**
     * Привести настройки к схеме: лишние ключи отбрасываются, недопустимые значения —
     * значение по умолчанию
     *
     * @param array $input
     * @return array
     */
    public static function normalize(array $input): array
    {
        $out = [];

        foreach (static::schema() as $field) {
            $key = $field['key'];
            $out[$key] = array_key_exists($key, $input)
                ? static::cast($field, $input[$key])
                : ($field['default'] ?? null);
        }

        return $out;
    }

    /**
     * Привести значение поля к типу
     *
     * @param array $field
     * @param mixed $value
     * @return mixed
     */
    protected static function cast(array $field, $value)
    {
        $default = $field['default'] ?? null;

        switch ($field['type']) {
            case 'bool':
                return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? (bool) $default;

            case 'number':
                if (!is_numeric($value)) return $default;
                $number = $value + 0;
                if (isset($field['min'])) $number = max($field['min'], $number);
                if (isset($field['max'])) $number = min($field['max'], $number);

                return $number;

            case 'select':
            case 'currency':
            case 'period':
                return array_key_exists((string) $value, static::options($field)) ? (string) $value : $default;

            case 'entity':
                if (!is_array($value) || !in_array($value['type'] ?? null, (array) ($field['entities'] ?? []), true)) return $default;

                return ['type' => (string) $value['type'], 'id' => trim((string) ($value['id'] ?? ''))];

            case 'list':
                return is_array($value) ? array_values($value) : ($default ?? []);

            default:
                return $value === null ? $default : Str::limit((string) $value, (int) ($field['max'] ?? 5000), '');
        }
    }

    /*** РАЗМЕРЫ ***/

    /**
     * 'ШxВ' → [ш, в]
     *
     * @param string $size
     * @return int[]
     */
    public static function parseSize(string $size): array
    {
        $parts = explode('x', strtolower(trim($size)));

        return [(int) ($parts[0] ?? 0), (int) ($parts[1] ?? 0)];
    }

    /**
     * Размеры виджета как пары [ш, в]
     *
     * @return array
     */
    public static function sizeList(): array
    {
        return array_map(fn($size) => static::parseSize($size), static::sizes());
    }

    /**
     * Размер разрешён виджету
     *
     * @param int $w
     * @param int $h
     * @return bool
     */
    public static function allows(int $w, int $h): bool
    {
        return in_array([$w, $h], static::sizeList(), true);
    }

    /**
     * Ближайший разрешённый размер (после ресайза мышью)
     *
     * @param int $w
     * @param int $h
     * @return int[]
     */
    public static function nearestSize(int $w, int $h): array
    {
        $best = static::parseSize(static::defaultSize());
        $distance = PHP_INT_MAX;

        foreach (static::sizeList() as [$sw, $sh]) {
            $d = abs($sw - $w) + abs($sh - $h);
            if ($d < $distance) {
                $distance = $d;
                $best = [$sw, $sh];
            }
        }

        return $best;
    }

    /**
     * Описание для библиотеки и JS страницы
     *
     * @return array
     */
    public static function meta(): array
    {
        return [
            'id' => static::id(),
            'name' => static::name(),
            'category' => static::category(),
            'description' => static::description(),
            'icon' => static::icon(),
            'sizes' => static::sizes(),
            'default_size' => static::defaultSize(),
            'uses_currency' => static::usesCurrency(),
            'uses_period' => static::usesPeriod(),
            'needs_setup' => collect(static::fields())->contains(fn($field) => !empty($field['required'])),
        ];
    }

    /*** ФОРМАТ ***/

    /**
     * Число коротко: 6 800 000 → «6,8 млн», 950 000 → «950 тыс.», 1 234 → «1 234»
     *
     * @param float|int|null $number
     * @return string
     */
    public static function compact(float|int|null $number): string
    {
        $number = (float) $number;
        $abs = abs($number);

        [$divider, $suffix] = match (true) {
            $abs >= 1e9 => [1e9, ' млрд'],
            $abs >= 1e6 => [1e6, ' млн'],
            $abs >= 1e4 => [1e3, ' тыс.'],
            default => [1, ''],
        };

        $value = $number / $divider;
        $precision = $divider === 1 || abs($value) >= 100 ? 0 : 1;

        $text = number_format($value, $precision, ',', ' ');
        if ($precision && str_ends_with($text, ',0')) {
            $text = substr($text, 0, -2);
        }

        return $text . $suffix;
    }

    /**
     * Сумма с символом валюты: коротко («6,8 млн ₽») или полностью через tools()->cost_normalize()
     *
     * @param float|int|null $amount null — «—»
     * @param string $symbol
     * @param bool $short
     * @return string
     */
    public static function money(float|int|null $amount, string $symbol = '₽', bool $short = true): string
    {
        if ($amount === null) {
            return '—';
        }

        $text = $short ? static::compact($amount) : tools()->cost_normalize(round((float) $amount));

        return $text . ' ' . $symbol;
    }

    /*** РАЗМЕР БЛОКА ***/

    /**
     * Ступень ширины блока: xs (1–2 колонки), sm (3–5), md (6–11), lg (12–23), xl (24+).
     *
     * Размер блока произвольный (пользователь может отжать замок размеров), поэтому вьюхи
     * смотрят на ступень, а не на точный размер
     *
     * @param int $w
     * @return string
     */
    public static function wClass(int $w): string
    {
        return static::step($w, static::W_STEPS);
    }

    /**
     * Ступень высоты блока: xs (1 ячейка), sm (2), md (3–5), lg (6–9), xl (10+)
     *
     * @param int $h
     * @return string
     */
    public static function hClass(int $h): string
    {
        return static::step($h, static::H_STEPS);
    }

    /**
     * Ступень по таблице «ступень => верхняя граница»
     *
     * @param int $value
     * @param array $steps
     * @return string
     */
    protected static function step(int $value, array $steps): string
    {
        foreach ($steps as $name => $max) {
            if ($value <= $max) return $name;
        }

        return 'xl';
    }

    /**
     * Сколько строк списка или таблицы влезет в блок высотой $h ячеек
     *
     * @param int $h
     * @param bool $head есть ли заголовок виджета
     * @param int $extra занято чем-то ещё (шапка таблицы, итог), px
     * @return int не меньше 1
     */
    public static function rowsFit(int $h, bool $head = true, int $extra = 0): int
    {
        $free = $h * static::CELL - ($head ? 30 : 0) - 16 - $extra;

        return max(1, (int) floor($free / static::ROW));
    }

    /**
     * Строк с запасом: сколько влезет на самом широком экране. Список с таким числом строк
     * кладётся в .desk-fit — лишние спрячет osmo-desktop-fit.js по реальной высоте блока
     *
     * @param int $h
     * @param bool $head
     * @param int $extra
     * @return int не меньше 1
     */
    public static function rowsMax(int $h, bool $head = true, int $extra = 0): int
    {
        $free = $h * static::CELL_MAX - ($head ? 30 : 0) - 16 - $extra;

        return max(1, (int) floor($free / static::ROW));
    }

    /*** ГРАФИК ***/

    /**
     * Блок графика ApexCharts. Разметку превращает в график osmo-desktop-charts.js
     * после вставки HTML; график занимает всё свободное место блока и пересчитывается,
     * когда блок меняет размер
     *
     * Описание: type (line, area, bar, donut, radialBar), series (как у ApexCharts),
     * categories (подписи оси X), labels (для donut), colors (имена цветов Metronic:
     * primary, success, warning, danger, info, gray-500), stacked, horizontal, legend,
     * sparkline (без осей и сетки), money + symbol (подсказка с валютой), label.
     *
     * @param array $config
     * @param string $class дополнительные классы блока
     * @return string
     */
    public static function chart(array $config, string $class = ''): string
    {
        $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT);

        return '<div class="desk-chart ' . e($class) . '" data-chart="' . e($json) . '"></div>';
    }

    /**
     * Спарклайн: линия без осей и подсказок под крупным числом
     *
     * @param array $values значения по порядку
     * @param string $color цвет Metronic
     * @param string $class
     * @return string
     */
    public static function sparkline(array $values, string $color = 'primary', string $class = ''): string
    {
        return static::chart([
            'type' => 'area',
            'sparkline' => true,
            'tooltip' => false,
            'colors' => [$color],
            'series' => [['name' => '', 'data' => array_values($values)]],
        ], $class);
    }

    /*** ОТРИСОВКА ***/

    /**
     * Виджет целиком: оболочка (заголовок, заливка, шрифт) + тело
     *
     * @param int $w
     * @param int $h
     * @param array $settings сырые настройки (будут нормализованы)
     * @param DesktopContext $ctx
     * @param bool $preview превью в библиотеке: образцовые данные, без ссылок
     * @param bool $fresh сбросить кэш данных
     * @param array $view состояние просмотра блока (см. viewState())
     * @return string
     */
    public function html(int $w, int $h, array $settings, DesktopContext $ctx, bool $preview = false, bool $fresh = false, array $view = []): string
    {
        $settings = static::normalize($settings);

        // блок в одну ячейку высотой: заголовок съел бы почти всё место — не показываем его
        // (и тело считает строки без заголовка), настройка при этом не меняется
        if ($h <= 1) {
            $settings['show_title'] = false;
        }

        return view('pub.desktop.partials.widget', [
            'widget' => static::class,
            'w' => $w,
            'h' => $h,
            'settings' => $settings,
            'body' => $this->body($w, $h, $settings, $ctx, $preview, $fresh, $view),
            'url' => $preview ? null : static::sourceUrl($settings),
            'preview' => $preview,
        ])->render();
    }

    /**
     * Тело виджета. Ошибка данных или вьюхи не роняет стол — вместо тела плашка
     *
     * @param int $w
     * @param int $h
     * @param array $settings
     * @param DesktopContext $ctx
     * @param bool $preview
     * @param bool $fresh
     * @param array $view сырое состояние просмотра блока
     * @return string
     */
    public function body(int $w, int $h, array $settings, DesktopContext $ctx, bool $preview = false, bool $fresh = false, array $view = []): string
    {
        $settings = static::normalize($settings);
        $view = static::viewState($view);

        try {
            // состояние просмотра — третьим аргументом: виджетам без него лишний аргумент не мешает
            $data = $preview ? $this->sample($settings, $ctx, $view) : $this->cachedData($settings, $ctx, $fresh, $view);

            return view(static::view(), [
                'widget' => static::class,
                'w' => $w,
                'h' => $h,
                'size' => $w . 'x' . $h,
                'dw' => static::wClass($w),
                'dh' => static::hClass($h),
                'rows' => static::rowsFit($h, !empty($settings['show_title'])),
                'rows_max' => static::rowsMax($h, !empty($settings['show_title'])),
                'settings' => $settings,
                'data' => $data,
                'ctx' => $ctx,
                'preview' => $preview,
                'view' => $view,
            ])->render();
        } catch (\Throwable $e) {
            report($e);

            return '<div class="desk-error"><i class="fa-light fa-triangle-exclamation"></i> '
                . e('Не удалось показать виджет') . '</div>';
        }
    }

    /**
     * Данные с кэшем; настройки оформления в ключ не входят
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @param bool $fresh
     * @param array $view состояние просмотра блока (уже отобранное viewState())
     * @return array
     */
    protected function cachedData(array $settings, DesktopContext $ctx, bool $fresh = false, array $view = []): array
    {
        $ttl = static::ttl();

        if ($ttl <= 0) {
            return $this->data($settings, $ctx, $view);
        }

        $view_keys = collect(static::schema())->where('group', 'view')->pluck('key')->all();
        $key = 'desktop_widget:' . static::id() . ':'
            . md5(json_encode([array_diff_key($settings, array_flip($view_keys)), $ctx->cacheKey(), $view]));

        if ($fresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, $ttl, fn() => $this->data($settings, $ctx, $view));
    }
}
