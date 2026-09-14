<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\PaymentCalendar\Services\PaymentCalendarService;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;

/**
 * Оплаты: план на период (patch v30) — сколько денег ждём по плановым датам
 * (payments.date_plan, amount_plan), как это разложено по неделям или месяцам
 * и сколько из этого уже закрыто фактом.
 *
 * Отбор — PaymentCalendarService::rows() с отбором платёжного календаря по умолчанию
 * (спецификации в работе плюс уже оплаченные платежи): стол не показывает больше,
 * чем страница. Отменённые спецификации из плана выпадают — денег по ним никто не ждёт.
 *
 * План пересчитывается в валюту виджета по текущему курсу, факт — по курсу на дату
 * поступления (так же считает платёжный календарь). Платежи, для которых курса нет,
 * не суммируются, а попадают в skipped.
 */
class PaymentsPlanWidget extends Widget
{
    /** Сколько столбиков разбивки максимум — дальше неделя заменяется месяцем */
    public const MAX_BUCKETS = 40;

    public static function id(): string
    {
        return 'payments_plan';
    }

    public static function name(): string
    {
        return 'Оплаты: план на период';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Плановые платежи за период: сумма, разбивка по неделям или месяцам и что уже закрыто фактом';
    }

    public static function icon(): string
    {
        return 'fa-calendar-days';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x4', '16x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 110;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function usesPeriod(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 600;
    }

    public static function available(User $user): bool
    {
        return (bool) $user->can_do('payment_calendar_view');
    }

    public static function fields(): array
    {
        return [
            ['key' => 'split', 'type' => 'select', 'label' => 'Разбивка', 'default' => 'auto', 'hint' => '«Авто» — недели на коротком периоде, месяцы на длинном',
                'options' => ['auto' => 'Авто', 'week' => 'По неделям', 'month' => 'По месяцам']],
            ['key' => 'done', 'type' => 'bool', 'label' => 'Показывать закрытое фактом', 'default' => true],
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'options' => fn() => ['all' => 'Все партнёры'] + PaymentCalendarService::partners()
                    ->mapWithKeys(fn($partner) => [(string) $partner->id => (string) $partner->name])
                    ->all()],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('payment_calendar.index', array_filter([
            'partner' => ($settings['partner'] ?? 'all') !== 'all' ? (int) $settings['partner'] : null,
            'all_years' => 1,
        ]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // образец за текущий квартал с разбивкой из настроек — столбики те же, что посчитал бы data():
        // прошедшие закрыты целиком, текущий — частично, будущие — нет
        [$from, $to] = [now()->startOfQuarter(), now()->endOfQuarter()];
        $split = static::split($settings, $from, $to);
        $buckets = static::buckets($split, $from, $to);
        $today = static::bucketKey($split, now());

        $total = ['amount' => 0.0, 'count' => 0, 'done' => 0.0, 'done_count' => 0];
        $i = 0;
        foreach ($buckets as $key => $bucket) {
            $amount = (($i * 7) % 5 + 2) * ($split === 'week' ? 180000.0 : 900000.0);
            $count = 1 + ($i * 3) % 4;
            $done = $key < $today ? $amount : ($key === $today ? round($amount * .4) : 0.0);

            $buckets[$key] = array_merge($bucket, ['amount' => $amount, 'count' => $count, 'done' => $done]);
            $total['amount'] += $amount;
            $total['count'] += $count;
            $total['done'] += $done;
            if ($done > 0) $total['done_count'] += $count;
            $i++;
        }

        return [
            'amount' => $total['amount'], 'count' => $total['count'], 'done' => $total['done'], 'done_count' => $total['done_count'],
            'percent' => $total['amount'] > 0 ? round($total['done'] / $total['amount'] * 100, 1) : null,
            'buckets' => array_values($buckets), 'split' => $split,
            'label' => 'Текущий квартал', 'dates' => $from->format('d.m.Y') . ' – ' . $to->format('d.m.Y'),
            'symbol' => '₽', 'skipped' => 0,
        ];
    }

    /**
     * План на период, разбивка и закрытая фактом часть
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['amount', 'count', 'done', 'done_count', 'percent', 'buckets', 'split', 'label', 'dates', 'symbol', 'skipped']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        [$from, $to] = [$period['from'], $period['to']];

        $partner = (string) ($settings['partner'] ?? 'all');
        $rows = PaymentCalendarService::rows([
            'spec_status' => PaymentCalendarService::SPEC_STATUS_DEFAULT,
            'partner' => $partner !== 'all' ? [(int) $partner] : [],
        ])->filter(
            fn($row) => $row->date_plan && $row->state !== 'canceled' && $row->date_plan->between($from, $to)
        );

        $split = static::split($settings, $from, $to);
        $buckets = static::buckets($split, $from, $to);

        $total = ['amount' => 0.0, 'count' => 0, 'done' => 0.0, 'done_count' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            // план — по текущему курсу, как на странице календаря
            $plan = CurrencyService::convertAmount((float) $row->amount_plan, $row->currency_slug, $currency, now());

            if ($plan === null) {
                $total['skipped']++;
                continue;
            }

            $key = static::bucketKey($split, $row->date_plan);
            $total['amount'] += $plan;
            $total['count']++;

            if (isset($buckets[$key])) {
                $buckets[$key]['amount'] += $plan;
                $buckets[$key]['count']++;
            }

            if (!$row->date_fact) continue;

            // закрыто фактом — по курсу на дату поступления
            $fact = CurrencyService::convertAmount((float) $row->amount_fact, $row->currency_slug, $currency, $row->date_fact);
            if ($fact === null) continue;

            $total['done'] += $fact;
            $total['done_count']++;
            if (isset($buckets[$key])) $buckets[$key]['done'] += $fact;
        }

        return [
            'amount' => round($total['amount'], 2),
            'count' => $total['count'],
            'done' => round($total['done'], 2),
            'done_count' => $total['done_count'],
            'percent' => $total['amount'] > 0 ? round($total['done'] / $total['amount'] * 100, 1) : null,
            'buckets' => array_values($buckets),
            'split' => $split,
            'label' => $period['label'],
            'dates' => $period['dates'],
            'symbol' => $ctx->symbol($currency),
            'skipped' => $total['skipped'],
        ];
    }

    /**
     * Разбивка: неделя или месяц («Авто» — по длине периода, но не больше MAX_BUCKETS столбиков)
     *
     * @param array $settings
     * @param Carbon $from
     * @param Carbon $to
     * @return string week|month
     */
    protected static function split(array $settings, Carbon $from, Carbon $to): string
    {
        $weeks = (int) ceil(($from->copy()->startOfDay()->diffInDays($to->copy()->startOfDay()) + 1) / 7);
        $choice = (string) ($settings['split'] ?? 'auto');

        if ($choice === 'auto') {
            return $weeks <= 10 ? 'week' : 'month';
        }

        return $choice === 'week' && $weeks > static::MAX_BUCKETS ? 'month' : $choice;
    }

    /**
     * Пустые столбики за весь период: видно и те недели (месяцы), где платежей нет
     *
     * @param string $split
     * @param Carbon $from
     * @param Carbon $to
     * @return array ключ => ['label', 'title', 'amount', 'count', 'done']
     */
    protected static function buckets(string $split, Carbon $from, Carbon $to): array
    {
        $ret = [];
        $cursor = $split === 'week' ? $from->copy()->startOfWeek() : $from->copy()->startOfMonth();

        while ($cursor <= $to && count($ret) < static::MAX_BUCKETS) {
            $ret[$cursor->format('Y-m-d')] = [
                'label' => $split === 'week'
                    ? $cursor->format('d.m')
                    : $cursor->locale('ru')->isoFormat('MMM'),
                'title' => $split === 'week'
                    ? $cursor->format('d.m.Y') . ' – ' . $cursor->copy()->endOfWeek()->format('d.m.Y')
                    : $cursor->locale('ru')->isoFormat('MMMM YYYY'),
                'amount' => 0.0,
                'count' => 0,
                'done' => 0.0,
            ];

            $split === 'week' ? $cursor->addWeek() : $cursor->addMonthNoOverflow();
        }

        return $ret;
    }

    /**
     * Ключ столбика для даты плана
     *
     * @param string $split
     * @param Carbon $date
     * @return string
     */
    protected static function bucketKey(string $split, Carbon $date): string
    {
        return $split === 'week'
            ? $date->copy()->startOfWeek()->format('Y-m-d')
            : $date->copy()->startOfMonth()->format('Y-m-d');
    }
}
