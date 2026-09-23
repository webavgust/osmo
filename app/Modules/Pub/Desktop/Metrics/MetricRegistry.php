<?php

namespace App\Modules\Pub\Desktop\Metrics;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\CrmMonitor\Services\CrmMismatchService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\ExternalProposal\Models\ExternalProposal;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalStatus;
use App\Modules\Pub\Proposal\Services\ProposalStatusService;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/**
 * Реестр показателей рабочего стола (patch v30) — источник для виджета «Число».
 *
 * Показатель: ключ => [
 *     'label' — подпись, 'group' — группа («КП», «Партнёры»), 'hint' — пояснение,
 *     'unit' — money | count | percent,
 *     'period' — bool, зависит ли от периода,
 *     'forecast' — bool, необязательно: прогнозный показатель (в идущем периоде есть будущие
 *         даты) — «к прошлому» сравнивается с прошлым периодом целиком, а фактический —
 *         по то же число (DesktopContext::previousRange(), $to_date),
 *     'value' — callable(DesktopContext $ctx, array $settings, Carbon $from, Carbon $to): ?float,
 *     'available' — callable(User): bool, необязательно,
 *     'url' — callable(): ?string, необязательно — страница-источник,
 * ].
 * Деньги считаются в валюте $ctx->currencyFor($settings); суммы без курса не учитываются.
 * Добавить показатель = дописать элемент в метод своей группы (или новый метод в all()).
 */
class MetricRegistry
{
    /** Кэш описаний на процесс */
    protected static ?array $metrics = null;

    /**
     * Все показатели: ключ => описание
     *
     * @return array
     */
    public static function all(): array
    {
        return static::$metrics ??= array_merge(
            static::proposals(),
            static::partners(),
            static::external(),
            static::crm(),
            static::payments(),
            static::keys(),
            static::funnel(),
        );
    }

    /**
     * Показатель по ключу
     *
     * @param string $key
     * @return array|null
     */
    public static function find(string $key): ?array
    {
        return static::all()[$key] ?? null;
    }

    /**
     * Показатель доступен пользователю
     *
     * @param string $key
     * @param User|null $user null — без проверки прав
     * @return bool
     */
    public static function availableFor(string $key, ?User $user): bool
    {
        $metric = static::find($key);
        if ($metric === null) return false;
        if ($user === null || !isset($metric['available'])) return true;

        return (bool) call_user_func($metric['available'], $user);
    }

    /**
     * Варианты для выбора: ключ => «Группа · Подпись», только доступные
     *
     * @param User|null $user
     * @return array
     */
    public static function options(?User $user = null): array
    {
        $out = [];

        foreach (static::all() as $key => $metric) {
            if (!static::availableFor($key, $user)) continue;

            $out[$key] = static::title($key);
        }

        return $out;
    }

    /**
     * Полная подпись показателя: «КП · В работе, сумма»
     *
     * @param string $key
     * @return string
     */
    public static function title(string $key): string
    {
        $metric = static::find($key);

        return $metric === null ? $key : $metric['group'] . ' · ' . $metric['label'];
    }

    /**
     * Значение показателя. Для показателя с периодом без границ — период виджета
     *
     * @param string $key
     * @param DesktopContext $ctx
     * @param array $settings нормализованные настройки виджета
     * @param Carbon|null $from
     * @param Carbon|null $to
     * @return float|null null — неизвестный показатель или нет данных
     */
    public static function value(string $key, DesktopContext $ctx, array $settings, ?Carbon $from = null, ?Carbon $to = null): ?float
    {
        $metric = static::find($key);
        if ($metric === null) return null;

        if ($from === null || $to === null) {
            $period = $ctx->periodFor($settings);
            $from ??= $period['from'];
            $to ??= $period['to'];
        }

        $value = call_user_func($metric['value'], $ctx, $settings, $from, $to);

        return $value === null ? null : (float) $value;
    }

    /** Шаги ряда по времени: ключ => [подпись, метод начала отрезка, формат подписи] */
    public const STEPS = [
        'day' => ['По дням', 'startOfDay', 'D MMM'],
        'week' => ['По неделям', 'startOfWeek', 'D MMM'],
        'month' => ['По месяцам', 'startOfMonth', 'MMM'],
        'quarter' => ['По кварталам', 'startOfQuarter', 'Q [кв.] YY'],
    ];

    /**
     * Ряд показателя по времени: значение за каждый из последних $count отрезков.
     *
     * Нужен графику, спарклайну и сравнению. Показатели, не зависящие от периода
     * ($metric['period'] = false), ряда не имеют — у них значение «на сейчас»
     *
     * @param string $key
     * @param DesktopContext $ctx
     * @param array $settings
     * @param string $step day | week | month | quarter
     * @param int $count сколько отрезков, включая текущий
     * @param Carbon|null $end последний отрезок (по умолчанию — текущий)
     * @return array [['label' => 'сен', 'title' => '01.09.2026 — 30.09.2026', 'value' => 1234.5], …]
     */
    public static function series(string $key, DesktopContext $ctx, array $settings, string $step = 'month', int $count = 12, ?Carbon $end = null): array
    {
        $metric = static::find($key);
        if ($metric === null || empty($metric['period'])) {
            return [];
        }

        $count = max(1, min(60, $count));
        [, $start_of, $format] = static::STEPS[$step] ?? static::STEPS['month'];
        $cursor = ($end ? $end->copy() : now())->{$start_of}();

        $out = [];
        for ($i = $count - 1; $i >= 0; $i--) {
            $from = static::stepBack($cursor->copy(), $step, $i);
            $to = static::stepEnd($from->copy(), $step);

            $out[] = [
                'label' => $from->locale('ru')->isoFormat($format),
                'title' => $from->format('d.m.Y') . ' — ' . $to->format('d.m.Y'),
                'value' => (float) (static::value($key, $ctx, $settings, $from, $to) ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Сдвинуть начало отрезка назад на $steps шагов
     *
     * @param Carbon $date
     * @param string $step
     * @param int $steps
     * @return Carbon
     */
    protected static function stepBack(Carbon $date, string $step, int $steps): Carbon
    {
        return match ($step) {
            'day' => $date->subDays($steps),
            'week' => $date->subWeeks($steps),
            'quarter' => $date->subQuarters($steps),
            default => $date->subMonths($steps),
        };
    }

    /**
     * Конец отрезка, который начинается датой $date
     *
     * @param Carbon $date
     * @param string $step
     * @return Carbon
     */
    protected static function stepEnd(Carbon $date, string $step): Carbon
    {
        return match ($step) {
            'day' => $date->endOfDay(),
            'week' => $date->endOfWeek(),
            'quarter' => $date->endOfQuarter(),
            default => $date->endOfMonth(),
        };
    }

    /**
     * Показатели, у которых есть ряд по времени: ключ => подпись с группой
     *
     * @param User|null $user
     * @return array
     */
    public static function periodOptions(?User $user = null): array
    {
        $out = [];
        foreach (static::options($user) as $key => $label) {
            $metric = static::find($key);
            if ($metric && !empty($metric['period'])) $out[$key] = $label;
        }

        return $out;
    }

    /*** ПОКАЗАТЕЛИ ***/

    /**
     * Коммерческие предложения: считаются по последним редакциям групп
     *
     * @return array
     */
    protected static function proposals(): array
    {
        $url = static::routeUrl('proposal.index');

        return [
            'proposals.in_work_count' => [
                'label' => 'В работе, шт.', 'group' => 'КП', 'unit' => 'count', 'period' => false, 'url' => $url,
                'hint' => 'КП, последняя редакция которых в статусе «В работе»',
                'value' => fn() => static::latestWithStatus(ProposalStatus::IN_WORK)->count(),
            ],
            'proposals.in_work_sum' => [
                'label' => 'В работе, сумма', 'group' => 'КП', 'unit' => 'money', 'period' => false, 'url' => $url,
                'hint' => 'Основной вариант последней редакции, по курсу на сегодня',
                'value' => fn(DesktopContext $ctx, array $settings) => static::mainSum(
                    static::latestWithStatus(ProposalStatus::IN_WORK), $ctx->currencyFor($settings)
                ),
            ],
            'proposals.sent_count' => [
                'label' => 'Отправлено за период, шт.', 'group' => 'КП', 'unit' => 'count', 'period' => true, 'url' => $url,
                'hint' => 'КП, первая редакция которых отправлена в периоде',
                'value' => fn(DesktopContext $ctx, array $settings, Carbon $from, Carbon $to) => static::groupsSentBetween($from, $to)->count(),
            ],
            'proposals.won_count' => [
                'label' => 'Выиграно за период, шт.', 'group' => 'КП', 'unit' => 'count', 'period' => true, 'url' => $url,
                'hint' => 'Выигранные КП, датированные периодом: первой оплатой по спецификации, без неё — сменой статуса',
                'value' => fn(DesktopContext $ctx, array $settings, Carbon $from, Carbon $to) => static::wonBetween($from, $to)->count(),
            ],
            'proposals.won_sum' => [
                'label' => 'Выиграно за период, сумма', 'group' => 'КП', 'unit' => 'money', 'period' => true, 'url' => $url,
                'hint' => 'Основной вариант выигранных в периоде КП (дата — первая оплата по спецификации или смена статуса), по курсу на сегодня',
                'value' => fn(DesktopContext $ctx, array $settings, Carbon $from, Carbon $to) => static::mainSum(
                    static::wonBetween($from, $to), $ctx->currencyFor($settings)
                ),
            ],
            'proposals.conversion' => [
                'label' => 'Конверсия', 'group' => 'КП', 'unit' => 'percent', 'period' => false, 'url' => $url,
                'hint' => 'Доля выигранных среди решённых КП (выиграно и проиграно)',
                'value' => fn() => ProposalStatusService::conversion(),
            ],
        ];
    }

    /**
     * Партнёры и компании
     *
     * @return array
     */
    protected static function partners(): array
    {
        return [
            'partners.active_count' => [
                'label' => 'Активные, шт.', 'group' => 'Партнёры', 'unit' => 'count', 'period' => false,
                'hint' => 'Партнёры с отметкой «активен»', 'url' => static::routeUrl('partner.index'),
                'value' => fn() => Partner::where('active', 1)->count(),
            ],
            'companies.count' => [
                'label' => 'Всего, шт.', 'group' => 'Компании', 'unit' => 'count', 'period' => false,
                'hint' => 'Все компании портала', 'url' => static::routeUrl('company.index'),
                'value' => fn() => Company::count(),
            ],
        ];
    }

    /**
     * КП внешних систем
     *
     * @return array
     */
    protected static function external(): array
    {
        return [
            'external.pending_count' => [
                'label' => 'Не перенесены, шт.', 'group' => 'Внешние КП', 'unit' => 'count', 'period' => false,
                'hint' => 'КП OSMOVIEW CP, ещё не перенесённые в портал', 'url' => static::routeUrl('external_proposal.index'),
                'value' => fn() => ExternalProposal::source()->transferred(false)->count(),
            ],
        ];
    }

    /**
     * Сверка с Битрикс24
     *
     * @return array
     */
    protected static function crm(): array
    {
        return [
            'crm.mismatch_count' => [
                'label' => 'Расхождения с КП, шт.', 'group' => 'Битрикс24', 'unit' => 'count', 'period' => false,
                'hint' => 'КП с любым расхождением: сумма, валюта, стадия, нет сделки или расчёта',
                'url' => static::routeUrl('crm_monitor.index'),
                'value' => fn() => CrmMismatchService::rows()->count(),
            ],
        ];
    }

    /**
     * Оплаты: считаются так же, как виджет «Оплаты за период»
     *
     * @return array
     */
    protected static function payments(): array
    {
        $widget = \App\Modules\Pub\Desktop\Widgets\Finance\PaymentsFactWidget::class;
        $available = fn(\App\Modules\Pub\User\Models\User $user) => $widget::available($user);
        $url = fn() => $widget::sourceUrl([]);

        return [
            'payments.fact_sum' => [
                'label' => 'Факт за период, сумма', 'group' => 'Оплаты', 'unit' => 'money', 'period' => true,
                'hint' => 'Фактические оплаты по курсу на дату оплаты',
                'available' => $available, 'url' => $url,
                'value' => fn(DesktopContext $ctx, array $settings, Carbon $from, Carbon $to)
                    => $widget::total($from, $to, $ctx->currencyFor($settings))['amount'],
            ],
            'payments.fact_count' => [
                'label' => 'Факт за период, шт.', 'group' => 'Оплаты', 'unit' => 'count', 'period' => true,
                'hint' => 'Количество фактических оплат в периоде',
                'available' => $available, 'url' => $url,
                'value' => fn(DesktopContext $ctx, array $settings, Carbon $from, Carbon $to)
                    => $widget::total($from, $to, $ctx->currencyFor($settings))['count'],
            ],
        ];
    }

    /**
     * Ключи: считаются так же, как виджет «Истекающие ключи»
     *
     * @return array
     */
    protected static function keys(): array
    {
        $widget = \App\Modules\Pub\Desktop\Widgets\Keys\KeysExpiringWidget::class;
        $data = fn(DesktopContext $ctx, array $settings, int $days) => (new $widget)->data(
            $widget::normalize(['days' => $days, 'expired' => false, 'currency' => $ctx->currencyFor($settings)]),
            $ctx
        );
        $url = fn() => $widget::sourceUrl($widget::normalize(['days' => 30]));

        return [
            'keys.expiring_30' => [
                'label' => 'Истекают за 30 дней, шт.', 'group' => 'Ключи', 'unit' => 'count', 'period' => false,
                'hint' => 'Действующие ключи, срок которых закончится в ближайшие 30 дней',
                'url' => $url,
                'value' => fn(DesktopContext $ctx, array $settings) => $data($ctx, $settings, 30)['count'],
            ],
            'keys.renewal_90' => [
                'label' => 'Продления за 90 дней, сумма', 'group' => 'Ключи', 'unit' => 'money', 'period' => false,
                'hint' => 'Сумма продления ключей, истекающих в ближайшие 90 дней, по курсу на сегодня',
                'url' => $url,
                'value' => fn(DesktopContext $ctx, array $settings) => $data($ctx, $settings, 90)['amount'],
            ],
        ];
    }

    /**
     * Воронка продаж (зеркало Битрикс24): суммы по активным стадиям, как карточки
     * страницы воронки, но без её фильтра и в валюте стола
     *
     * @return array
     */
    protected static function funnel(): array
    {
        $url = static::routeUrl('dashboard.index');
        $metrics = [
            'sales' => ['Сумма сделок', 'Сумма сделок в активных стадиях'],
            'licenses' => ['Лицензии', 'Сумма лицензий в активных стадиях'],
            'services' => ['Услуги', 'Сумма услуг в активных стадиях'],
            'devcost' => ['Стоимость разработки', 'Стоимость разработки в активных стадиях'],
            'platform' => ['Платформенные доработки', 'Стоимость платформенных доработок в активных стадиях'],
        ];

        $out = [];
        foreach ($metrics as $method => [$label, $hint]) {
            $out['funnel.' . $method] = [
                'label' => $label, 'group' => 'Воронка', 'unit' => 'money', 'period' => false,
                'hint' => $hint . ', без фильтра страницы воронки',
                'url' => $url,
                'value' => fn(DesktopContext $ctx, array $settings) => (float) (new \App\Modules\Bitrix\Dashboard\Services\DashboardDataService(
                    $ctx->currencyFor($settings), false
                ))->{$method}()['amount'],
            ];
        }

        return $out;
    }

    /*** ВСПОМОГАТЕЛЬНОЕ ***/

    /**
     * Ссылка на страницу по имени маршрута (нет маршрута — null)
     *
     * @param string $name
     * @return \Closure
     */
    protected static function routeUrl(string $name): \Closure
    {
        return fn() => Route::has($name) ? route($name) : null;
    }

    /**
     * Последние редакции КП в статусе; пустой статус — «В работе», как в ProposalStatusService::counters()
     *
     * @param ProposalStatus $status
     * @return Collection
     */
    public static function latestWithStatus(ProposalStatus $status): Collection
    {
        return ProposalStatusService::latestIterations()
            ->filter(fn($row) => ($row->status ?? ProposalStatus::IN_WORK->value) === $status->value)
            ->values();
    }

    /**
     * Группы КП, первая редакция которых отправлена в периоде; без второстепенных (patch v33)
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return Collection коды групп
     */
    public static function groupsSentBetween(Carbon $from, Carbon $to): Collection
    {
        return Proposal::query()
            ->whereIn('id', fn($query) => $query->selectRaw('MIN(id)')->from('proposals')->groupBy('group'))
            // patch v33: второстепенные КП в расчётах не участвуют
            ->counted()
            ->whereBetween('sended_at', [$from->copy()->startOfDay()->format('Y-m-d H:i:s'), $to->copy()->endOfDay()->format('Y-m-d H:i:s')])
            ->pluck('group')
            ->unique()
            ->values();
    }

    /**
     * Выигранные КП (последние редакции), выигрыш по которым датирован периодом —
     * дата выигрыша из wonDates()
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return Collection
     */
    public static function wonBetween(Carbon $from, Carbon $to): Collection
    {
        $from = $from->copy()->startOfDay();
        $to = $to->copy()->endOfDay();

        $won = static::latestWithStatus(ProposalStatus::WON);
        $dates = static::wonDates($won);

        return $won
            ->filter(function ($row) use ($dates, $from, $to) {
                $date = $dates[(string) $row->group] ?? null;

                return $date !== null && $date->between($from, $to);
            })
            ->values();
    }

    /**
     * Даты выигрыша КП (решение владельца 23.09.2026). КП прикреплено к спецификации
     * (contract_specification_proposals) и по ней есть фактическая оплата — выигрыш
     * датируется первой оплатой по всем неотменённым спецификациям группы. Иначе —
     * сменой статуса (status_changed_at), у старых записей — отправкой редакции.
     * Статус не проверяется: передавать выигранные КП. Один запрос на все группы
     *
     * @param Collection $rows редакции КП (по одной на группу)
     * @return array код группы => Carbon|null
     */
    public static function wonDates(Collection $rows): array
    {
        $dates = [];
        foreach ($rows as $row) {
            $dates[(string) $row->group] = $row->status_changed_at ?? $row->sended_at;
        }

        if ($dates === []) return [];

        $paid = DB::table('contract_specification_proposals as l')
            ->join('contract_specifications as s', 's.id', '=', 'l.contract_specification_id')
            ->join('payments as p', 'p.contract_specification_id', '=', 's.id')
            ->whereIn('l.proposal_group', array_keys($dates))
            ->where(fn($query) => $query->whereNull('s.status')->orWhere('s.status', '!=', 'canceled'))
            ->whereNotNull('p.date_fact')
            ->groupBy('l.proposal_group')
            ->selectRaw('l.proposal_group, MIN(p.date_fact) as first_paid')
            ->pluck('first_paid', 'proposal_group');

        foreach ($paid as $group => $date) {
            $dates[(string) $group] = Carbon::parse($date);
        }

        return $dates;
    }

    /**
     * Даты решения по КП: у выигранных — дата выигрыша (wonDates()), у остальных —
     * смена статуса, у старых записей — отправка редакции
     *
     * @param Collection $rows редакции КП (по одной на группу)
     * @return array код группы => Carbon|null
     */
    public static function decidedDates(Collection $rows): array
    {
        $dates = [];
        foreach ($rows as $row) {
            $dates[(string) $row->group] = $row->status_changed_at ?? $row->sended_at;
        }

        $won = $rows->filter(fn($row) => (string) $row->status === ProposalStatus::WON->value);

        return array_replace($dates, static::wonDates($won));
    }

    /**
     * Сумма основных вариантов КП в валюте. Основной вариант — первый в Proposal::variants()
     * (is_main desc, id); пересчёт по курсу на дату, суммы без курса не учитываются
     *
     * @param Collection $proposals редакции КП
     * @param string $currency код валюты результата
     * @param Carbon|null $date дата курса, по умолчанию сегодня
     * @return float
     */
    public static function mainSum(Collection $proposals, string $currency, ?Carbon $date = null): float
    {
        if ($proposals->isEmpty()) return 0.0;

        $main = DB::table('proposal_variants')
            ->whereIn('proposal_id', $proposals->pluck('id'))
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->get(['proposal_id', 'cost_total'])
            ->groupBy('proposal_id')
            ->map(fn($rows) => (float) $rows->first()->cost_total);

        // суммы по валютам КП, затем один пересчёт на валюту
        $by_currency = [];
        foreach ($proposals as $proposal) {
            if (!$main->has($proposal->id)) continue;

            $slug = (string) $proposal->currency_slug;
            $by_currency[$slug] = ($by_currency[$slug] ?? 0.0) + $main->get($proposal->id);
        }

        $total = 0.0;
        foreach ($by_currency as $slug => $amount) {
            $converted = CurrencyService::convertAmount($amount, $slug, $currency, $date ?? now());
            if ($converted !== null) {
                $total += $converted;
            }
        }

        return $total;
    }
}
