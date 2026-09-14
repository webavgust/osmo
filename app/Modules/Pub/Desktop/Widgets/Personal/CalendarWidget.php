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
 * Два вида: «месяц» — сетка дней с точками событий, «ближайшие» — список.
 * Вид выбирается настройкой, но в низком блоке сетка не помещается, поэтому вьюха
 * всегда показывает список. События берутся связью User::calendar_events()
 * (как на странице календаря), клик открывает сайдбар события calendar.sidebar_show.
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

    /** Сколько событий за месяц читаем ради точек в сетке */
    public const MONTH_CAP = 300;

    /** Сколько точек рисуем в одном дне */
    public const DOTS = 3;

    public static function id(): string { return 'calendar'; }

    public static function name(): string { return 'Календарь'; }

    public static function category(): string { return 'personal'; }

    public static function description(): string
    {
        return 'Месяц с точками событий или список ближайших событий календаря';
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
     * Сетка месяца и список ближайших событий
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['month', 'weekdays', 'weeks', 'rows', 'count', 'days']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $now = now();
        $user = $ctx->user;
        $days = (int) $settings['days'];

        if (!$user) {
            return $this->pack($now, [], [], $days, 0);
        }

        // сетка: с понедельника недели 1-го числа по воскресенье недели последнего
        $from = $now->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $to = $now->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);

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

        // считаем события, а не отметки: многодневное занимает несколько дней сетки
        return $this->pack($now, $this->spread($month, $from, $to), $this->rows($upcoming, $now), $days, $month->count());
    }

    /**
     * Образцовые данные для превью: события выдуманы, база не спрашивается
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $now = now();
        $days = (int) $settings['days'];

        // несколько дней текущего месяца с событиями
        $by_day = [];
        foreach ([[2, 'primary', 'Планёрка'], [9, 'success', 'ВКС «Русал»'], [9, 'warning', 'Счёт по договору № 118'],
                     [16, 'danger', 'Дедлайн по КП AA-794'], [17, 'info', 'Обучение партнёров'], [24, 'success', 'Внутрянка']] as $i => $event) {
            [$day, $color, $title] = $event;
            $date = $now->copy()->startOfMonth()->addDays($day)->format('Y-m-d');
            $by_day[$date]['count'] = ($by_day[$date]['count'] ?? 0) + 1;
            $by_day[$date]['colors'][] = $color;
            $by_day[$date]['titles'][] = $title;
            $by_day[$date]['id'] = $i + 1;
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

        return $this->pack($now, $by_day, $rows, $days, 6);
    }

    /**
     * Собрать ответ: сетка месяца + список
     *
     * @param Carbon $now
     * @param array $by_day события по дням ['Y-m-d' => ['count', 'colors', 'titles', 'id']]
     * @param array $rows строки списка ближайших
     * @param int $days горизонт списка
     * @param int $count событий в месяце
     * @return array
     */
    protected function pack(Carbon $now, array $by_day, array $rows, int $days, int $count): array
    {
        return [
            'month' => Tools::MONTH_NAME[$now->month] . ' ' . $now->year,
            'weekdays' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'],
            'weeks' => $this->weeks($now, $by_day),
            'rows' => $rows,
            'count' => $count,
            'days' => $days,
        ];
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
     * Недели месяца: по 7 дней, лишние дни соседних месяцев — пустые
     *
     * @param Carbon $now
     * @param array $by_day
     * @return array
     */
    protected function weeks(Carbon $now, array $by_day): array
    {
        $day = $now->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY);
        $last = $now->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY);
        $today = $now->format('Y-m-d');
        $month = $now->month;

        $weeks = [];
        while ($day->lte($last)) {
            $week = [];
            for ($i = 0; $i < 7; $i++) {
                $key = $day->format('Y-m-d');
                $events = $by_day[$key] ?? null;
                $in = $day->month === $month;

                $week[] = [
                    'day' => $day->day,
                    'date' => $day->format('d.m.Y'),
                    'in' => $in,
                    'today' => $key === $today,
                    'weekend' => $day->isWeekend(),
                    'count' => $in && $events ? (int) $events['count'] : 0,
                    'dots' => $in && $events ? array_slice(array_unique($events['colors']), 0, static::DOTS) : [],
                    // названия событий в ячейке — только в крупном блоке, остальные в title
                    'events' => $in && $events ? array_slice(array_map(fn($color, $title) => ['color' => $color, 'title' => $title], $events['colors'], $events['titles']), 0, static::DOTS) : [],
                    'title' => $in && $events ? $day->format('d.m.Y') . ': ' . implode(' · ', $events['titles']) : $day->format('d.m.Y'),
                    // один день — один сайдбар первого события; клика по пустому дню нет
                    'url' => $in && $events ? route('calendar.sidebar_show', $events['id']) : null,
                ];

                $day->addDay();
            }
            $weeks[] = $week;
        }

        return $weeks;
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
}
