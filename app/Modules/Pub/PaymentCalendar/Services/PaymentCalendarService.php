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
 */
class PaymentCalendarService
{
    /** Что считаем «скоро» — дней до планового платежа */
    public const SOON_DAYS = 14;

    /** Основная валюта: в ней показываем крупные показатели */
    public const MAIN_CURRENCY = ['RUB', 'RUR'];

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
        ];
    }

    /**
     * Платежи с рассчитанным состоянием
     *
     * @param array $params [
     *     'year' => int|null — год планового платежа,
     *     'state' => string|null — фильтр по состоянию,
     *     'q' => string|null — поиск по компании, партнёру, спецификации, договору,
     *     'partner' => int|null,
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
                'c.proposal_id',
                'co.name as company_name',
                'pa.id as partner_id',
                'pa.name as partner_name',
            ]);

        if (!empty($params['year'])) {
            $builder->where(function ($builder) use ($params) {
                $builder->whereYear('p.date_plan', (int) $params['year'])
                    ->orWhereYear('p.date_fact', (int) $params['year']);
            });
        }

        if (!empty($params['partner'])) {
            $builder->where('pa.id', (int) $params['partner']);
        }

        if (!empty($params['q'])) {
            $like = '%' . trim($params['q']) . '%';
            $builder->where(function ($builder) use ($like) {
                $builder->where('co.name', 'like', $like)
                    ->orWhere('pa.name', 'like', $like)
                    ->orWhere('s.name', 'like', $like)
                    ->orWhere('c.number', 'like', $like);
            });
        }

        $rows = collect($builder->orderByRaw('COALESCE(p.date_plan, p.date_fact) IS NULL, COALESCE(p.date_plan, p.date_fact)')->get());

        $rows = $rows->map(function ($row) {
            $row->date_plan = $row->date_plan ? Carbon::parse($row->date_plan) : null;
            $row->date_fact = $row->date_fact ? Carbon::parse($row->date_fact) : null;
            $row->contract_date = $row->contract_date ? Carbon::parse($row->contract_date) : null;

            $row->state = static::state($row);
            $row->state_decorate = static::states()[$row->state];
            $row->days_left = $row->date_plan && !$row->date_fact
                ? (int) now()->startOfDay()->diffInDays($row->date_plan, false)
                : null;

            // сколько ждём: факт, если оплачен, иначе план
            $row->amount = $row->date_fact ? (float) $row->amount_fact : (float) $row->amount_plan;
            $row->currency_slug = $row->currency_slug ?: 'RUB';

            return $row;
        });

        if (!empty($params['state'])) {
            $rows = $rows->where('state', $params['state']);
        }

        return $rows->values();
    }

    /**
     * Состояние платежа
     *
     * @param object $row
     * @return string
     */
    public static function state($row): string
    {
        if (!empty($row->date_fact)) return 'paid';
        if (!empty($row->is_unknown) || empty($row->date_plan)) return 'unknown';

        $days = (int) now()->startOfDay()->diffInDays($row->date_plan, false);

        return match (true) {
            $days < 0 => 'overdue',
            $days <= static::SOON_DAYS => 'soon',
            default => 'planned',
        };
    }

    /**
     * Показатели: просрочка, ожидания, поступления
     *
     * @param Collection $rows
     * @return array
     */
    public static function summary(Collection $rows): array
    {
        $overdue = $rows->where('state', 'overdue');
        $soon = $rows->where('state', 'soon');
        $unknown = $rows->where('state', 'unknown');
        $paid = $rows->where('state', 'paid');

        $paid_month = $paid->filter(
            fn($row) => $row->date_fact && $row->date_fact->isSameMonth(now())
        );

        return [
            'overdue' => [
                'count' => $overdue->count(),
                'amount' => static::totals($overdue),
                'max_days' => (int) abs($overdue->min('days_left') ?? 0),
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
            'plan_total' => static::totals($rows->whereNull('date_fact')),
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
                'label' => $date->locale('ru')->translatedFormat('LLLL'),
                'is_current' => $date->isSameMonth(now()),
                'is_past' => $date->endOfMonth()->isPast(),
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
