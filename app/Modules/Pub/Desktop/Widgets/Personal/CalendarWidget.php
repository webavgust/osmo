<?php

namespace App\Modules\Pub\Desktop\Widgets\Personal;

use App\Facades\Tools;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Календарь (patch v30): события календаря портала текущего пользователя.
 *
 * Два вида: «месяц» — сетка дней с кружками событий, «ближайшие» — список.
 * Вид выбирается настройкой, но в низком блоке сетка не помещается, поэтому вьюха
 * всегда показывает список. События берутся связью User::calendar_events()
 * (как на странице календаря), клик по событию открывает сайдбар calendar.sidebar_show.
 *
 * Месяц листается как в Windows 11: стрелки, выбор месяца и года плитками, «сегодня».
 * Листаемый месяц, режим шапки и выбранный день — состояние просмотра блока
 * (viewState()): живёт в браузере, в настройках не хранится. Клик по дню открывает
 * панель дня: события дня и «+ Новое событие» с этой датой.
 */
class CalendarWidget extends Widget
{
    /** Вид блока */
    public const VIEWS = [
        'month' => 'Месяц',
        'list' => 'Ближайшие события',
    ];

    /** Цвета событий Metronic: чужое значение из базы в класс не пойдёт */
    public const COLORS = ['primary', 'info', 'success', 'warning', 'danger', 'secondary', 'dark'];

    /** Сколько событий за месяц читаем ради кружков в сетке */
    public const MONTH_CAP = 300;

    /** Сколько событий одного дня читаем для панели дня */
    public const DAY_CAP = 50;

    /** Сколько названий событий рисуем в ячейке дня крупного блока */
    public const DOTS = 3;

    /** Режимы шапки: выбор месяца и выбор года (сетка дней — отсутствие режима) */
    public const MODES = ['months', 'years'];

    /** Границы листания по годам */
    public const YEAR_MIN = 1970;
    public const YEAR_MAX = 2100;

    /** Короткие названия месяцев для плиток выбора месяца */
    public const MONTH_SHORT = [1 => 'янв', 2 => 'фев', 3 => 'мар', 4 => 'апр', 5 => 'май', 6 => 'июн',
        7 => 'июл', 8 => 'авг', 9 => 'сен', 10 => 'окт', 11 => 'ноя', 12 => 'дек'];

    /** Дни недели по ISO-номеру — для подписи панели дня */
    public const WEEKDAY_NAME = [1 => 'понедельник', 2 => 'вторник', 3 => 'среда', 4 => 'четверг',
        5 => 'пятница', 6 => 'суббота', 7 => 'воскресенье'];

    /** Светлый кружок дня с событиями: у secondary и dark светлый вариант Metronic почти белый */
    public const TINTS = [
        'secondary' => 'bg-gray-200 text-gray-700',
        'dark' => 'bg-gray-300 text-gray-900',
    ];

    public static function id(): string { return 'calendar'; }

    public static function name(): string { return 'Календарь'; }

    public static function category(): string { return 'personal'; }

    public static function description(): string
    {
        return 'Месяц с листанием и событиями по дням или список ближайших событий календаря';
    }

    public static function icon(): string { return 'fa-calendar-days'; }

    public static function sizes(): array { return ['8x8', '16x8', '16x16']; }

    public static function defaultSize(): string { return '8x8'; }

    public static function order(): int { return 400; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'view', 'type' => 'select', 'label' => 'Вид', 'default' => 'month', 'options' => static::VIEWS, 'hint' => 'В низком блоке всегда список'],
            ['key' => 'days', 'type' => 'number', 'label' => 'Горизонт списка, дней', 'default' => 14, 'min' => 1, 'max' => 365],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Событий в списке', 'default' => 12, 'min' => 1, 'max' => 50],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('calendar.index');
    }

    /**
     * Состояние просмотра: листаемый месяц, режим шапки, выбранный день.
     * Всё чужое и невалидное выбрасывается — блок откроется на текущем месяце
     *
     * @param array $input сырое состояние из браузера
     * @return array ['month' => 'Y-m', 'mode' => 'months'|'years', 'day' => 'Y-m-d'] — только допустимые ключи
     */
    public static function viewState(array $input): array
    {
        $view = [];
        $year_ok = fn($year) => (int) $year >= static::YEAR_MIN && (int) $year <= static::YEAR_MAX;

        $month = $input['month'] ?? null;
        if (is_string($month) && preg_match('/^(\d{4})-(\d{2})$/', $month, $m)
            && $year_ok($m[1]) && (int) $m[2] >= 1 && (int) $m[2] <= 12) {
            $view['month'] = $month;
        }

        $mode = $input['mode'] ?? null;
        if (is_string($mode) && in_array($mode, static::MODES, true)) {
            $view['mode'] = $mode;
        }

        $day = $input['day'] ?? null;
        if (is_string($day) && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $day, $m)
            && $year_ok($m[1]) && checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            $view['day'] = $day;
        }

        return $view;
    }

    /**
     * Сетка опорного месяца, панель дня и список ближайших событий
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @param array $view состояние просмотра (см. viewState())
     * @return array ['month', 'nav', 'weekdays', 'weeks', 'months', 'years', 'day', 'rows', 'count', 'days']
     */
    public function data(array $settings, DesktopContext $ctx, array $view = []): array
    {
        $now = now();
        $anchor = static::anchor($view);
        $focus = static::focus($view, $now);
        $user = $ctx->user;
        $days = (int) $settings['days'];

        if (!$user) {
            return $this->pack($anchor, $now, $view, [], [], $this->dayPanel($focus, $now, []), $days, 0);
        }

        // сетка: с понедельника недели 1-го числа по воскресенье недели последнего
        $from = $anchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $to = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

        $month = $user->calendar_events()
            ->whereNotNull('start')
            ->where('start', '<=', $to)
            ->where(function ($query) use ($from) {
                $query->where('end', '>=', $from)->orWhereNull('end');
            })
            ->orderBy('start')
            ->limit(static::MONTH_CAP)
            ->get();

        $horizon = $now->copy()->addDays($days)->endOfDay();
        $upcoming = $user->calendar_events()
            ->whereNotNull('start')
            ->where('start', '<=', $horizon)
            ->where(function ($query) use ($now) {
                $query->where('end', '>=', $now)->orWhere(function ($query) use ($now) {
                    $query->whereNull('end')->where('start', '>=', $now);
                });
            })
            ->orderBy('start')
            ->limit((int) $settings['limit'])
            ->get();

        // панель дня: события, которые идут в этот день (многодневные — тоже)
        $day_from = $focus->copy()->startOfDay();
        $day_to = $focus->copy()->endOfDay();
        $day_events = $user->calendar_events()
            ->whereNotNull('start')
            ->where('start', '<=', $day_to)
            ->where(function ($query) use ($day_from, $day_to) {
                $query->where('end', '>=', $day_from)->orWhere(function ($query) use ($day_from, $day_to) {
                    $query->whereNull('end')->whereBetween('start', [$day_from, $day_to]);
                });
            })
            ->orderBy('start')
            ->limit(static::DAY_CAP)
            ->get();

        // считаем события, а не отметки: многодневное занимает несколько дней сетки
        return $this->pack($anchor, $now, $view, $this->spread($month, $from, $to), $this->rows($upcoming, $now),
            $this->dayPanel($focus, $now, $this->dayItems($day_events, $focus)), $days, $month->count());
    }

    /**
     * Образцовые данные для превью: события выдуманы, база не спрашивается
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @param array $view состояние просмотра (см. viewState())
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx, array $view = []): array
    {
        $now = now();
        $anchor = static::anchor($view);
        $focus = static::focus($view, $now);
        $days = (int) $settings['days'];

        // несколько дней опорного месяца с событиями
        $by_day = [];
        $mark = function (string $date, string $color, string $title, int $id) use (&$by_day) {
            $by_day[$date]['count'] = ($by_day[$date]['count'] ?? 0) + 1;
            $by_day[$date]['colors'][] = $color;
            $by_day[$date]['titles'][] = $title;
            $by_day[$date]['id'] = $by_day[$date]['id'] ?? $id;
        };
        foreach ([[2, 'primary', 'Планёрка'], [9, 'success', 'ВКС «Русал»'], [9, 'warning', 'Счёт по договору № 118'],
                     [16, 'danger', 'Дедлайн по КП AA-794'], [17, 'info', 'Обучение партнёров'], [24, 'success', 'Внутрянка']] as $i => $event) {
            [$day, $color, $title] = $event;
            $mark($anchor->copy()->startOfMonth()->addDays($day)->format('Y-m-d'), $color, $title, $i + 1);
        }

        // панель дня: четыре события; тот же день в сетке отмечен ими же
        $items = [];
        foreach ([['весь день', 'Обучение партнёров', 'info'], ['10:00', 'Планёрка', 'primary'],
                     ['15:00', 'ВКС «Русал» по поставке оборудования', 'success'], ['16:30', 'Дедлайн по КП AA-794', 'danger']] as $i => $event) {
            [$time, $title, $color] = $event;
            $items[] = ['id' => 100 + $i, 'time' => $time, 'title' => $title, 'color' => $color, 'url' => null];
            $mark($focus->format('Y-m-d'), $color, ($time === 'весь день' ? '' : $time . ' ') . $title, 100 + $i);
        }

        // список ближайших: столько строк, сколько разрешено настройкой (до 40), чтобы
        // высокий блок было чем заполнить
        $pool = [['15:00', 'ВКС «Русал»', 'success'], ['11:30', 'Внутрянка по Пулково', 'warning'], ['весь день', 'Обучение партнёров', 'info'],
            ['10:00', 'Планёрка', 'primary'], ['16:30', 'Дедлайн по КП AA-794', 'danger'], ['12:00', 'Счёт по договору № 118', 'warning'],
            ['14:00', 'Демо платформы для «Аэрофлота»', 'primary'], ['09:30', 'Звонок партнёру в Пекин', 'info']];
        $rows = [];
        for ($i = 0, $n = min(40, max(1, (int) $settings['limit'])); $i < $n; $i++) {
            [$time, $title, $color] = $pool[$i % count($pool)];
            $start = $now->copy()->addDays(intdiv($i * $days, max($n, 1)));
            $rows[] = [
                'date' => $start->format('d.m'),
                'when' => $start->isToday() ? 'сегодня' : ($start->isTomorrow() ? 'завтра' : $start->format('d.m')),
                'time' => $time, 'title' => $title, 'color' => $color, 'icon' => '', 'today' => $start->isToday(), 'url' => null,
            ];
        }

        return $this->pack($anchor, $now, $view, $by_day, $rows, $this->dayPanel($focus, $now, $items), $days, 10);
    }

    /**
     * Собрать ответ: шапка, сетка месяца, плитки месяцев и лет, панель дня, список
     *
     * @param Carbon $anchor опорный (листаемый) месяц
     * @param Carbon $now сегодня
     * @param array $view состояние просмотра
     * @param array $by_day события по дням ['Y-m-d' => ['count', 'colors', 'titles', 'id']]
     * @param array $rows строки списка ближайших
     * @param array $day панель дня (см. dayPanel())
     * @param int $days горизонт списка
     * @param int $count событий в сетке месяца
     * @return array
     */
    protected function pack(Carbon $anchor, Carbon $now, array $view, array $by_day, array $rows, array $day, int $days, int $count): array
    {
        $nav = $this->nav($anchor, $now);

        return [
            'month' => $nav['title'],
            'nav' => $nav,
            'weekdays' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
            'weeks' => $this->weeks($anchor, $now, $by_day, $view['day'] ?? null),
            'months' => $this->months($anchor, $now),
            'years' => $this->years($anchor, $now),
            'day' => $day,
            'rows' => $rows,
            'count' => $count,
            'days' => $days,
        ];
    }

    /**
     * Опорный месяц: листаемый из состояния просмотра или текущий
     *
     * @param array $view
     * @return Carbon первое число месяца, 00:00
     */
    protected static function anchor(array $view): Carbon
    {
        return isset($view['month'])
            ? Carbon::createFromFormat('!Y-m', $view['month'])->startOfMonth()
            : now()->startOfMonth();
    }

    /**
     * День панели: выбранный в состоянии просмотра или сегодня
     *
     * @param array $view
     * @param Carbon $now
     * @return Carbon начало дня
     */
    protected static function focus(array $view, Carbon $now): Carbon
    {
        return isset($view['day'])
            ? Carbon::createFromFormat('!Y-m-d', $view['day'])->startOfDay()
            : $now->copy()->startOfDay();
    }

    /**
     * Месяц со сдвигом; за границами листания — null (стрелка гаснет)
     *
     * @param Carbon $anchor
     * @param int $months
     * @return string|null 'Y-m'
     */
    protected static function shift(Carbon $anchor, int $months): ?string
    {
        $month = $anchor->copy()->addMonthsNoOverflow($months);

        return $month->year >= static::YEAR_MIN && $month->year <= static::YEAR_MAX ? $month->format('Y-m') : null;
    }

    /**
     * Шапка месяца: заголовок и куда ведут стрелки в каждом режиме
     *
     * @param Carbon $anchor
     * @param Carbon $now
     * @return array ['month', 'title', 'year', 'prev', 'next', 'prev_year', 'next_year', 'decade', 'prev_decade', 'next_decade', 'is_current', 'today']
     */
    protected function nav(Carbon $anchor, Carbon $now): array
    {
        $decade = intdiv($anchor->year, 10) * 10;

        return [
            'month' => $anchor->format('Y-m'),
            'title' => Tools::MONTH_NAME[$anchor->month] . ' ' . $anchor->year,
            // короткий заголовок — для узкого блока
            'short' => static::MONTH_SHORT[$anchor->month] . ' ' . $anchor->year,
            'year' => $anchor->year,
            'prev' => static::shift($anchor, -1),
            'next' => static::shift($anchor, 1),
            'prev_year' => static::shift($anchor, -12),
            'next_year' => static::shift($anchor, 12),
            'decade' => $decade . '–' . ($decade + 9),
            'prev_decade' => static::shift($anchor, -120),
            'next_decade' => static::shift($anchor, 120),
            'is_current' => $anchor->format('Y-m') === $now->format('Y-m'),
            'today' => $now->format('Y-m'),
        ];
    }

    /**
     * Плитки выбора месяца: 12 месяцев опорного года
     *
     * @param Carbon $anchor
     * @param Carbon $now
     * @return array [['value' => 'Y-m', 'label', 'title', 'current', 'selected']]
     */
    protected function months(Carbon $anchor, Carbon $now): array
    {
        $months = [];

        for ($m = 1; $m <= 12; $m++) {
            $months[] = [
                'value' => sprintf('%04d-%02d', $anchor->year, $m),
                'label' => static::MONTH_SHORT[$m],
                'title' => Tools::MONTH_NAME[$m] . ' ' . $anchor->year,
                'current' => $anchor->year === $now->year && $m === $now->month,
                'selected' => $m === $anchor->month,
            ];
        }

        return $months;
    }

    /**
     * Плитки выбора года: десятилетие опорного года и по году с краёв (как в Windows 11)
     *
     * @param Carbon $anchor
     * @param Carbon $now
     * @return array [['value' => 'Y-m'|null, 'label', 'current', 'selected', 'outside']]
     */
    protected function years(Carbon $anchor, Carbon $now): array
    {
        $decade = intdiv($anchor->year, 10) * 10;
        $years = [];

        for ($year = $decade - 1; $year <= $decade + 10; $year++) {
            // год за границами листания — плитка без клика
            $ok = $year >= static::YEAR_MIN && $year <= static::YEAR_MAX;
            $years[] = [
                'value' => $ok ? sprintf('%04d-%02d', $year, $anchor->month) : null,
                'label' => (string) $year,
                'current' => $year === $now->year,
                'selected' => $year === $anchor->year,
                'outside' => $year < $decade || $year > $decade + 9,
            ];
        }

        return $years;
    }

    /**
     * Разложить события по дням отрезка: многодневное отмечает каждый свой день
     *
     * @param Collection $events
     * @param Carbon $from
     * @param Carbon $to
     * @return array ['Y-m-d' => ['count', 'colors', 'titles', 'id']]
     */
    protected function spread(Collection $events, Carbon $from, Carbon $to): array
    {
        $by_day = [];

        foreach ($events as $event) {
            // многодневное событие обрезаем по краям сетки (копии: даты в цикле сдвигаются)
            $start = $event->start->copy()->startOfDay();
            if ($start->lt($from)) $start = $from->copy()->startOfDay();
            $end = ($event->end ?: $event->start)->copy()->startOfDay();
            if ($end->gt($to)) $end = $to->copy()->startOfDay();

            for ($day = $start; $day->lte($end); $day->addDay()) {
                $key = $day->format('Y-m-d');
                $by_day[$key]['count'] = ($by_day[$key]['count'] ?? 0) + 1;
                $by_day[$key]['colors'][] = static::color($event->color);
                $by_day[$key]['titles'][] = ($event->all_day ? '' : $event->start->format('H:i') . ' ') . Str::limit((string) $event->title, 40);
                $by_day[$key]['id'] = $by_day[$key]['id'] ?? $event->id;
            }
        }

        return $by_day;
    }

    /**
     * Недели опорного месяца: по 7 дней, лишние дни соседних месяцев — без событий
     *
     * @param Carbon $anchor опорный месяц
     * @param Carbon $now сегодня: подсвечивается, только если попал в сетку
     * @param array $by_day
     * @param string|null $selected выбранный день 'Y-m-d'
     * @return array
     */
    protected function weeks(Carbon $anchor, Carbon $now, array $by_day, ?string $selected = null): array
    {
        $day = $anchor->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $last = $anchor->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $today = $now->format('Y-m-d');
        $month = $anchor->month;

        $weeks = [];
        while ($day->lte($last)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $day->format('Y-m-d');
                $events = $by_day[$key] ?? null;
                $in = $day->month === $month;

                $week[] = [
                    'day' => $day->day,
                    'key' => $key,
                    // месяц дня: клик по дню соседнего месяца листает на него
                    'ym' => $day->format('Y-m'),
                    'date' => $day->format('d.m.Y'),
                    'in' => $in,
                    'today' => $key === $today,
                    'selected' => $key === $selected,
                    'weekend' => $day->isWeekend(),
                    'count' => $in && $events ? (int) $events['count'] : 0,
                    // цвет кружка — по первому событию дня
                    'color' => $in && $events ? $events['colors'][0] : null,
                    // названия событий в ячейке — только в крупном блоке, остальные в title
                    'events' => $in && $events ? array_slice(array_map(fn($color, $title) => ['color' => $color, 'title' => $title], $events['colors'], $events['titles']), 0, static::DOTS) : [],
                    'title' => $in && $events ? $day->format('d.m.Y') . ': ' . implode(' · ', $events['titles']) : $day->format('d.m.Y'),
                ];

                $day->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * События панели дня
     *
     * @param Collection $events события, которые идут в этот день
     * @param Carbon $focus день панели
     * @return array [['id', 'time', 'title', 'color', 'url']]
     */
    protected function dayItems(Collection $events, Carbon $focus): array
    {
        $items = [];

        foreach ($events as $event) {
            $start = $event->start;
            $items[] = [
                'id' => $event->id,
                // многодневное, начатое раньше, — с какого числа идёт, а не чужое время начала
                'time' => $event->all_day ? 'весь день'
                    : ($start->lt($focus) ? 'с ' . $start->format($start->year === $focus->year ? 'd.m' : 'd.m.y') : $start->format('H:i')),
                'title' => Str::limit((string) $event->title, 70),
                'color' => static::color($event->color),
                'url' => route('calendar.sidebar_show', $event->id),
            ];
        }

        return $items;
    }

    /**
     * Панель дня: подпись, события и ссылка на создание события этой датой
     *
     * @param Carbon $focus
     * @param Carbon $now
     * @param array $items см. dayItems()
     * @return array ['key', 'label', 'date', 'weekday', 'today', 'items', 'add_url']
     */
    protected function dayPanel(Carbon $focus, Carbon $now, array $items): array
    {
        // год — только если день не в текущем году
        $date = $focus->day . ' ' . mb_strtolower(Tools::MONTH_NAME_D[$focus->month]) . ($focus->year !== $now->year ? ' ' . $focus->year : '');
        $weekday = static::WEEKDAY_NAME[$focus->dayOfWeekIso];

        return [
            'key' => $focus->format('Y-m-d'),
            'label' => $weekday . ', ' . $date,
            'date' => $date,
            'weekday' => $weekday,
            'today' => $focus->isSameDay($now),
            'items' => $items,
            'add_url' => route('calendar.sidebar_add', ['date' => $focus->format('Y-m-d')]),
        ];
    }

    /**
     * Строки списка ближайших событий
     *
     * @param Collection $events
     * @param Carbon $now
     * @return array
     */
    protected function rows(Collection $events, Carbon $now): array
    {
        $rows = [];

        foreach ($events as $event) {
            $start = $event->start;
            $rows[] = [
                'date' => $start->format($start->isCurrentYear() ? 'd.m' : 'd.m.y'),
                'when' => $start->isToday() ? 'сегодня' : ($start->isTomorrow() ? 'завтра' : $start->format($start->isCurrentYear() ? 'd.m' : 'd.m.y')),
                'time' => $event->all_day ? 'весь день' : $start->format('H:i'),
                'title' => Str::limit((string) $event->title, 70),
                'color' => static::color($event->color),
                'icon' => preg_match('/^[a-z0-9\- ]+$/i', (string) $event->title_icon) ? (string) $event->title_icon : '',
                'today' => $start->isToday(),
                'url' => route('calendar.sidebar_show', $event->id),
            ];
        }

        return $rows;
    }

    /**
     * Цвет события: только из списка Metronic
     *
     * @param string|null $color
     * @return string
     */
    protected static function color(?string $color): string
    {
        return in_array((string) $color, static::COLORS, true) ? (string) $color : 'primary';
    }

    /**
     * Классы светлого кружка дня с событиями
     *
     * @param string|null $color цвет первого события дня; null — событий нет
     * @return string
     */
    public static function tint(?string $color): string
    {
        if ($color === null) {
            return '';
        }
        $color = static::color($color);

        return static::TINTS[$color] ?? 'bg-light-' . $color . ' text-' . $color;
    }

    /**
     * Класс цветной точки события: bg-secondary на светлом фоне не видно
     *
     * @param string $color
     * @return string
     */
    public static function dot(string $color): string
    {
        $color = static::color($color);

        return $color === 'secondary' ? 'bg-gray-400' : 'bg-' . $color;
    }
}
