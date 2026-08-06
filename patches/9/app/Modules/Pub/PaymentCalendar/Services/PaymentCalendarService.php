<?php

namespace App\Modules\Pub\PaymentCalendar\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Платёжный календарь.
 *
 * Данные в payments уже есть (date_plan, date_fact, amount_plan, amount_fact,
 * delay, is_unknown), но отдельной страницы не было. Сервис собирает из них
 * просрочку, прогноз поступлений по месяцам и план/факт.
 *
 * Запрос идёт через DB::table, а не через связи Eloquent: платежей много,
 * а нужны только скалярные поля для таблицы и сумм.
 *
 * Что не попадает в просрочку:
 *   — платежи отменённых спецификаций (status = canceled),
 *   — платежи по архивным договорам (contracts.old), если не включить их явно.
 */
class PaymentCalendarService
{
    /** Что считаем «скоро» — дней до планового платежа */
    public const SOON_DAYS = 14;

    /** Основная валюта: в ней показываем крупные показатели */
    public const MAIN_CURRENCY = ['RUB', 'RUR'];

    /** Статус отменённой спецификации (ContractSpecificationStatus::CANCELED) */
    public const SPEC_CANCELED = 'canceled';

    /**
     * Состояния платежа
     *
     * @return array
     */
    public static function states(): array
    {
        return [
            'overdue' => ['label' => 'Просрочен', 'color' => 'danger', 'icon' => 'fa-triangle-exclamation'],
            'soon' => ['label' => 'Скоро', 'color' => 'warning', 'icon' => 'fa-hourglass-half'],
            'planned' => ['label' => 'В плане', 'color' => 'info', 'icon' => 'fa-calendar-days'],
            'paid' => ['label' => 'Оплачен', 'color' => 'success', 'icon' => 'fa-circle-check'],
            'unknown' => ['label' => 'Без даты', 'color' => 'secondary', 'icon' => 'fa-circle-question'],
            'canceled' => ['label' => 'Спец. отменена', 'color' => 'dark', 'icon' => 'fa-ban'],
        ];
    }

    /**
     * Возрастные корзины просрочки — чтобы видеть, из чего сложилась сумма
     *
     * @return array
     */
    public static function ages(): array
    {
        return [
            'd30' => ['label' => 'до 30 дней', 'from' => 0, 'to' => 30],
            'd90' => ['label' => '31–90 дней', 'from' => 31, 'to' => 90],
            'd365' => ['label' => '91–365 дней', 'from' => 91, 'to' => 365],
            'older' => ['label' => 'больше года', 'from' => 366, 'to' => 100000],
        ];
    }

    /**
     * Платежи с рассчитанным состоянием
     *
     * @param array $params [
     *     'year' => int|null — год планового или фактического платежа,
     *     'month' => int|null — месяц внутри года,
     *     'state' => string|null — фильтр по состоянию,
     *     'age' => string|null — возрастная корзина просрочки (см. ages()),
     *     'q' => string|null — поиск по компании, партнёру, спецификации, договору, КП,
     *     'partner' => int|null,
     *     'company' => int|null,
     *     'spec' => int|null,
     *     'archive' => bool — включать архивные договоры,
     * ]
     * @return Collection
     */
    public static function rows(array $params = []): Collection
    {
        $builder = DB::table('payments as p')
            ->leftJoin('contract_specifications as s', 's.id', '=', 'p.contract_specification_id')
            ->leftJoin('contracts as c', 'c.id', '=', 's.contract_id')
            ->leftJoin('companies as co', 'co.id', '=', DB::raw('COALESCE(s.company_id, c.company_id)'))
            ->leftJoin('partners as pa', 'pa.id', '=', 'c.partner_id')
            ->leftJoin('proposals as pr', 'pr.id', '=', 'c.proposal_id')
            ->select([
                'p.id',
                'p.date_plan',
                'p.date_fact',
                'p.delay',
                'p.amount_plan',
                'p.amount_fact',
                'p.is_unknown',
                's.id as spec_id',
                's.name as spec_name',
                's.amount as spec_amount',
                's.currency_slug',
                's.is_signed',
                's.status as spec_status',
                'c.id as contract_id',
                'c.number as contract_number',
                'c.date as contract_date',
                'c.old as contract_old',
                'c.proposal_id',
                'co.id as company_id',
                'co.name as company_name',
                'pa.id as partner_id',
                'pa.name as partner_name',
                'pr.group as proposal_group',
                'pr.number as proposal_number',
                'pr.name as proposal_name',
                'pr.iteration as proposal_iteration',
            ]);

        // архивные договоры показываем только по требованию
        if (empty($params['archive'])) {
            $builder->where(function ($builder) {
                $builder->where('c.old', 0)->orWhereNull('c.old');
            });
        }

        if (!empty($params['year'])) {
            $year = (int) $params['year'];
            $month = !empty($params['month']) ? (int) $params['month'] : null;

            $builder->where(function ($builder) use ($year, $month) {
                $builder->where(function ($builder) use ($year, $month) {
                    $builder->whereYear('p.date_plan', $year);
                    if ($month) $builder->whereMonth('p.date_plan', $month);
                })->orWhere(function ($builder) use ($year, $month) {
                    $builder->whereYear('p.date_fact', $year);
                    if ($month) $builder->whereMonth('p.date_fact', $month);
                });
            });
        }

        if (!empty($params['partner'])) {
            $builder->where('pa.id', (int) $params['partner']);
        }

        if (!empty($params['company'])) {
            $builder->where('co.id', (int) $params['company']);
        }

        if (!empty($params['spec'])) {
            $builder->where('s.id', (int) $params['spec']);
        }

        if (!empty($params['q'])) {
            $like = '%' . trim($params['q']) . '%';
            $builder->where(function ($builder) use ($like) {
                $builder->where('co.name', 'like', $like)
                    ->orWhere('pa.name', 'like', $like)
                    ->orWhere('s.name', 'like', $like)
                    ->orWhere('c.number', 'like', $like)
                    ->orWhere('pr.number', 'like', $like)
                    ->orWhere('pr.name', 'like', $like);
            });
        }

        $rows = collect($builder->orderByRaw('COALESCE(p.date_plan, p.date_fact) IS NULL, COALESCE(p.date_plan, p.date_fact)')->get());

        $rows = $rows->map(function ($row) {
            $row->date_plan = $row->date_plan ? Carbon::parse($row->date_plan) : null;
            $row->date_fact = $row->date_fact ? Carbon::parse($row->date_fact) : null;
            $row->contract_date = $row->contract_date ? Carbon::parse($row->contract_date) : null;

            $row->is_canceled = strtolower((string) $row->spec_status) === static::SPEC_CANCELED;
            $row->state = static::state($row);
            $row->state_decorate = static::states()[$row->state];
            $row->days_left = $row->date_plan && !$row->date_fact
                ? (int) now()->startOfDay()->diffInDays($row->date_plan, false)
                : null;
            $row->overdue_days = $row->state === 'overdue' ? abs((int) $row->days_left) : 0;
            $row->age = $row->state === 'overdue' ? static::age($row->overdue_days) : null;

            // сколько ждём: факт, если оплачен, иначе план
            $row->amount = $row->date_fact ? (float) $row->amount_fact : (float) $row->amount_plan;
            $row->currency_slug = $row->currency_slug ?: 'RUB';

            return $row;
        });

        if (!empty($params['state'])) {
            $rows = $rows->where('state', $params['state']);
        }

        if (!empty($params['age'])) {
            $rows = $rows->where('age', $params['age']);
        }

        return $rows->values();
    }

    /**
     * Состояние платежа
     *
     * Отменённая спецификация не даёт ни просрочки, ни плана: деньги по ней
     * никто не ждёт. Уже поступивший платёж остаётся оплаченным.
     *
     * @param object $row
     * @return string
     */
    public static function state($row): string
    {
        if (!empty($row->date_fact)) return 'paid';
        if (!empty($row->is_canceled)) return 'canceled';
        if (!empty($row->is_unknown) || empty($row->date_plan)) return 'unknown';

        $days = (int) now()->startOfDay()->diffInDays($row->date_plan, false);

        return match (true) {
            $days < 0 => 'overdue',
            $days <= static::SOON_DAYS => 'soon',
            default => 'planned',
        };
    }

    /**
     * Возрастная корзина по числу дней просрочки
     *
     * @param int $days
     * @return string
     */
    public static function age(int $days): string
    {
        foreach (static::ages() as $code => $age) {
            if ($days >= $age['from'] && $days <= $age['to']) return $code;
        }

        return 'older';
    }

    /**
     * Показатели: просрочка, ожидания, поступления
     *
     * @param Collection $rows
     * @return array
     */
    public static function summary(Collection $rows): array
    {
        $live = $rows->where('state', '!=', 'canceled');

        $overdue = $live->where('state', 'overdue');
        $soon = $live->where('state', 'soon');
        $unknown = $live->where('state', 'unknown');
        $paid = $rows->where('state', 'paid');
        $canceled = $rows->where('state', 'canceled');

        $paid_month = $paid->filter(
            fn($row) => $row->date_fact && $row->date_fact->isSameMonth(now())
        );

        // из чего сложилась просрочка
        $buckets = [];
        foreach (static::ages() as $code => $age) {
            $group = $overdue->where('age', $code);
            if ($group->isEmpty()) continue;

            $buckets[$code] = [
                'code' => $code,
                'label' => $age['label'],
                'count' => $group->count(),
                'amount' => static::totals($group),
            ];
        }

        return [
            'overdue' => [
                'count' => $overdue->count(),
                'amount' => static::totals($overdue),
                'max_days' => (int) $overdue->max('overdue_days'),
                'buckets' => $buckets,
            ],
            'soon' => [
                'count' => $soon->count(),
                'amount' => static::totals($soon),
            ],
            'paid_month' => [
                'count' => $paid_month->count(),
                'amount' => static::totals($paid_month),
            ],
            'unknown' => [
                'count' => $unknown->count(),
                'amount' => static::totals($unknown),
            ],
            'canceled' => [
                'count' => $canceled->count(),
                'amount' => static::totals($canceled),
            ],
            'plan_total' => static::totals($live->whereNull('date_fact')),
            'fact_total' => static::totals($paid),
        ];
    }

    /**
     * Суммы по валютам: ['main' => 123.0, 'other' => ['USD' => 45.0]]
     *
     * @param Collection $rows
     * @return array
     */
    public static function totals(Collection $rows): array
    {
        $ret = ['main' => 0.0, 'other' => []];

        foreach ($rows->groupBy('currency_slug') as $slug => $group) {
            $sum = (float) $group->sum('amount');

            if (in_array(strtoupper((string) $slug), static::MAIN_CURRENCY, true)) {
                $ret['main'] += $sum;
                continue;
            }

            $ret['other'][strtoupper((string) $slug)] = ($ret['other'][strtoupper((string) $slug)] ?? 0) + $sum;
        }

        return $ret;
    }

    /**
     * Разбивка по месяцам: план, факт, просрочка
     *
     * @param Collection $rows
     * @param int $year
     * @return Collection
     */
    public static function months(Collection $rows, int $year): Collection
    {
        $rows = $rows->where('state', '!=', 'canceled');
        $ret = collect();

        for ($month = 1; $month <= 12; $month++) {
            $date = Carbon::create($year, $month, 1);

            $plan = $rows->filter(
                fn($row) => $row->date_plan
                    && (int) $row->date_plan->year === $year
                    && (int) $row->date_plan->month === $month
            );

            $fact = $rows->filter(
                fn($row) => $row->date_fact
                    && (int) $row->date_fact->year === $year
                    && (int) $row->date_fact->month === $month
            );

            $overdue = $plan->where('state', 'overdue');

            $plan_sum = (float) $plan->sum(fn($row) => (float) $row->amount_plan);
            $fact_sum = (float) $fact->sum(fn($row) => (float) $row->amount_fact);

            $ret->push([
                'month' => $month,
                // LLLL в translatedFormat() — это признак високосного года, а не месяц:
                // отсюда и брались «0000». Название месяца даёт isoFormat('MMMM').
                'label' => $date->locale('ru')->isoFormat('MMMM'),
                'is_current' => $date->isSameMonth(now()),
                'is_past' => $date->copy()->endOfMonth()->isPast(),
                'plan_count' => $plan->count(),
                'plan' => $plan_sum,
                'fact_count' => $fact->count(),
                'fact' => $fact_sum,
                'overdue_count' => $overdue->count(),
                'overdue' => (float) $overdue->sum(fn($row) => (float) $row->amount_plan),
                'diff' => $fact_sum - $plan_sum,
            ]);
        }

        return $ret;
    }

    /**
     * Годы, за которые есть платежи
     *
     * @return array
     */
    public static function years(): array
    {
        $years = collect(DB::table('payments')
            ->selectRaw('DISTINCT YEAR(COALESCE(date_plan, date_fact)) as y')
            ->whereNotNull(DB::raw('COALESCE(date_plan, date_fact)'))
            ->pluck('y'))
            ->filter()
            ->map(fn($y) => (int) $y)
            ->sortDesc()
            ->values()
            ->all();

        if (empty($years)) $years = [(int) now()->year];

        return $years;
    }
}
