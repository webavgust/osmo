<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Facades\Tools;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Оплаты за период (patch v30): сумма фактических поступлений и сравнение
 * с предыдущим таким же отрезком.
 *
 * Отбор — как в платёжном календаре: платёж с датой факта считается оплаченным
 * и попадает в показатели при любом статусе спецификации (отбор по умолчанию
 * «в работе + оплаченные»). Пересчёт в валюту виджета — по курсу на дату оплаты;
 * оплаты без курса не суммируются, а считаются в skipped.
 */
class PaymentsFactWidget extends Widget
{
    public static function id(): string
    {
        return 'payments_fact';
    }

    public static function name(): string
    {
        return 'Оплаты за период';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Сколько денег пришло за период и как это к прошлому такому же отрезку';
    }

    public static function icon(): string
    {
        return 'fa-money-bill-wave';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x2', '4x4'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 100;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function usesPeriod(): bool
    {
        return true;
    }

    public static function available(User $user): bool
    {
        return (bool) $user->can_do('payment_calendar_view');
    }

    public static function fields(): array
    {
        return [
            ['key' => 'range', 'type' => 'select', 'label' => 'Отрезок', 'default' => 'period', 'options' => ['period' => 'Период стола или свой', 'days' => 'Последние N дней']],
            ['key' => 'days', 'type' => 'number', 'label' => 'N дней', 'default' => 30, 'min' => 1, 'max' => 365, 'hint' => 'Для отрезка „Последние N дней“'],
            ['key' => 'compare', 'type' => 'bool', 'label' => 'Сравнить с предыдущим отрезком', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('payment_calendar.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // образец за 30 дней по дням — для графика в крупном блоке
        $buckets = static::buckets(now()->subDays(29)->startOfDay(), now()->endOfDay());
        $values = [];
        foreach (array_keys(array_values($buckets['labels'])) as $i) {
            $values[] = (($i * 7) % 5) * 90000.0 + ($i % 4 === 0 ? 150000.0 : 0.0);
        }

        return [
            'value' => 6800000.0, 'count' => 14, 'previous' => 5620000.0, 'delta_percent' => 21.0,
            'label' => 'за 30 дней', 'symbol' => '₽', 'skipped' => 0,
            'series' => ['labels' => array_values($buckets['labels']), 'values' => $values],
        ];
    }

    /**
     * Сумма оплат за отрезок и за предыдущий отрезок
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['value', 'count', 'previous', 'delta_percent', 'label', 'symbol', 'skipped', 'series' => ['labels', 'values']]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);

        if ($settings['range'] === 'days') {
            $days = (int) $settings['days'];
            $from = now()->subDays($days - 1)->startOfDay();
            $to = now()->endOfDay();
            [$prev_from, $prev_to] = [$from->copy()->subDays($days), $to->copy()->subDays($days)];
            $label = 'за ' . $days . ' ' . Tools::morph($days, 'день', 'дня', 'дней');
        } else {
            $period = $ctx->periodFor($settings);
            [$from, $to, $label] = [$period['from'], $period['to'], $period['label']];
            [$prev_from, $prev_to] = DesktopContext::previousRange($period['key']);
        }

        // разбивка текущего отрезка по дням, неделям или месяцам — для графика в крупном блоке
        $buckets = static::buckets($from, $to);
        $current = static::total($from, $to, $currency, $buckets['step']);
        $previous = $settings['compare'] ? static::total($prev_from, $prev_to, $currency) : null;

        return [
            'series' => [
                'labels' => array_values($buckets['labels']),
                'values' => array_map(fn($key) => round($current['series'][$key] ?? 0.0), array_keys($buckets['labels'])),
            ],
            'value' => $current['amount'],
            'count' => $current['count'],
            'previous' => $previous['amount'] ?? null,
            'delta_percent' => !empty($previous['amount'])
                ? round(($current['amount'] - $previous['amount']) / abs($previous['amount']) * 100, 1)
                : null,
            'label' => $label,
            'symbol' => $ctx->symbol($currency),
            'skipped' => $current['skipped'],
        ];
    }

    /**
     * Фактические оплаты за даты в валюте по курсу на дату оплаты
     *
     * @param Carbon $from
     * @param Carbon $to
     * @param string $currency
     * @param string|null $step разбить суммы по корзинам графика (day, week, month) — см. buckets()
     * @return array ['amount' => float, 'count' => int, 'skipped' => int, 'series' => [ключ корзины => сумма]]
     */
    public static function total(Carbon $from, Carbon $to, string $currency, ?string $step = null): array
    {
        $groups = DB::table('payments as p')
            ->join('contract_specifications as s', 's.id', '=', 'p.contract_specification_id')
            ->whereNotNull('p.date_fact')
            ->whereBetween('p.date_fact', [$from->format('Y-m-d'), $to->format('Y-m-d')])
            ->groupBy('s.currency_slug', 'p.date_fact')
            ->selectRaw('s.currency_slug, p.date_fact, SUM(p.amount_fact) as amount, COUNT(*) as cnt')
            ->get();

        $ret = ['amount' => 0.0, 'count' => 0, 'skipped' => 0, 'series' => []];

        foreach ($groups as $group) {
            $amount = CurrencyService::convertAmount((float) $group->amount, $group->currency_slug, $currency, Carbon::parse($group->date_fact));

            if ($amount === null) {
                $ret['skipped'] += (int) $group->cnt;
                continue;
            }

            $ret['amount'] += $amount;
            $ret['count'] += (int) $group->cnt;

            if ($step !== null) {
                $key = static::bucketKey(Carbon::parse($group->date_fact), $step);
                $ret['series'][$key] = ($ret['series'][$key] ?? 0.0) + $amount;
            }
        }

        return $ret;
    }

    /**
     * Корзины отрезка для графика: по дням (до 31 дня), по неделям (до 120 дней), иначе по месяцам
     *
     * @param Carbon $from
     * @param Carbon $to
     * @return array ['step' => day|week|month, 'labels' => [ключ корзины => подпись]]
     */
    public static function buckets(Carbon $from, Carbon $to): array
    {
        $days = (int) $from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1;
        $step = $days <= 31 ? 'day' : ($days <= 120 ? 'week' : 'month');

        // курсор — с начала корзины, иначе неполный последний месяц или неделя потеряются
        $cursor = match ($step) {
            'day' => $from->copy()->startOfDay(),
            'week' => $from->copy()->startOfWeek(),
            default => $from->copy()->startOfMonth(),
        };

        $labels = [];
        // предохранитель: самый длинный отрезок стола — год, это 13 месяцев
        while ($cursor <= $to && count($labels) < 400) {
            $labels[static::bucketKey($cursor, $step)] = match ($step) {
                'day', 'week' => $cursor->format('d.m'),
                default => $cursor->locale('ru')->isoFormat('MMM YY'),
            };

            match ($step) {
                'day' => $cursor->addDay(),
                'week' => $cursor->addWeek(),
                default => $cursor->addMonthNoOverflow(),
            };
        }

        return ['step' => $step, 'labels' => $labels];
    }

    /**
     * Ключ корзины графика для даты
     *
     * @param \Carbon\CarbonInterface $date
     * @param string $step day, week или month
     * @return string
     */
    public static function bucketKey(\Carbon\CarbonInterface $date, string $step): string
    {
        return match ($step) {
            'day' => $date->format('Y-m-d'),
            'week' => $date->copy()->startOfWeek()->format('Y-m-d'),
            default => $date->format('Y-m'),
        };
    }
}
