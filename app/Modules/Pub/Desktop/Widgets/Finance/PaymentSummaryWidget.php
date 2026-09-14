<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\PaymentCalendar\Services\PaymentCalendarService;
use App\Modules\Pub\User\Models\User;

/**
 * Оплаты: план и факт (patch v30) — сводка за период: сколько ждали по плановым
 * датам, сколько пришло по датам факта, процент исполнения и отклонение.
 *
 * План — PaymentCalendarService::rows() с отбором платёжного календаря по умолчанию
 * (спецификации в работе плюс уже оплаченные), платежи с датой плана внутри периода;
 * отменённые спецификации в план не входят. Факт — PaymentsFactWidget::total(),
 * тот же отбор, что у виджета «Оплаты за период».
 *
 * План пересчитывается по текущему курсу, факт — по курсу на дату поступления.
 * Платежи без курса не суммируются, а попадают в skipped.
 */
class PaymentSummaryWidget extends Widget
{
    public static function id(): string
    {
        return 'payment_summary';
    }

    public static function name(): string
    {
        return 'Оплаты: план и факт';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Сводка за период: план, факт, процент исполнения и отклонение';
    }

    public static function icon(): string
    {
        return 'fa-scale-balanced';
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
        return 140;
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
            ['key' => 'open', 'type' => 'bool', 'label' => 'Показывать остаток плана', 'default' => true,
                'hint' => 'Плановые платежи периода, по которым факта ещё нет'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('payment_calendar.index', ['all_years' => 1]);
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // образец за текущий квартал по неделям — для графика в крупном блоке
        $buckets = PaymentsFactWidget::buckets(now()->startOfQuarter(), now()->endOfQuarter());
        $today = PaymentsFactWidget::bucketKey(now(), $buckets['step']);
        $plan = $fact = [];
        foreach (array_keys($buckets['labels']) as $i => $key) {
            $plan[] = 400000.0 + (($i * 7) % 5) * 150000.0;
            $fact[] = $key <= $today ? 300000.0 + (($i * 3) % 4) * 170000.0 : 0.0;
        }

        return [
            'series' => ['labels' => array_values($buckets['labels']), 'plan' => $plan, 'fact' => $fact],
            'plan' => 8550000.0, 'plan_count' => 8,
            'fact' => 6800000.0, 'fact_count' => 14,
            'open' => 1750000.0, 'open_count' => 3,
            'overdue' => 640000.0, 'overdue_count' => 1,
            'percent' => 79.5, 'diff' => -1750000.0,
            'label' => 'Текущий квартал', 'dates' => '01.07.2026 – 30.09.2026',
            'symbol' => '₽', 'skipped' => 0,
        ];
    }

    /**
     * План, факт, исполнение и отклонение за период
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['plan', 'plan_count', 'fact', 'fact_count', 'open', 'open_count', 'overdue', 'overdue_count', 'percent', 'diff', 'label', 'dates', 'symbol', 'skipped', 'series' => ['labels', 'plan', 'fact']]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        [$from, $to] = [$period['from'], $period['to']];

        $rows = PaymentCalendarService::rows(['spec_status' => PaymentCalendarService::SPEC_STATUS_DEFAULT])
            ->filter(fn($row) => $row->date_plan && $row->state !== 'canceled' && $row->date_plan->between($from, $to));

        $plan = ['amount' => 0.0, 'count' => 0, 'open' => 0.0, 'open_count' => 0, 'overdue' => 0.0, 'overdue_count' => 0, 'skipped' => 0];

        // разбивка периода для графика «план и факт»: по дням, неделям или месяцам
        $buckets = PaymentsFactWidget::buckets($from, $to);
        $plan_series = array_fill_keys(array_keys($buckets['labels']), 0.0);

        foreach ($rows as $row) {
            // план — по текущему курсу, как на странице календаря
            $amount = CurrencyService::convertAmount((float) $row->amount_plan, $row->currency_slug, $currency, now());

            if ($amount === null) {
                $plan['skipped']++;
                continue;
            }

            $plan['amount'] += $amount;
            $plan['count']++;

            $key = PaymentsFactWidget::bucketKey($row->date_plan, $buckets['step']);
            if (isset($plan_series[$key])) $plan_series[$key] += $amount;

            if ($row->date_fact) continue;

            // плана ждём до сих пор: остаток и просрочка внутри него
            $plan['open'] += $amount;
            $plan['open_count']++;

            if ($row->state !== 'overdue') continue;

            $plan['overdue'] += $amount;
            $plan['overdue_count']++;
        }

        // факт считаем тем же отбором, что и виджет «Оплаты за период»
        $fact = PaymentsFactWidget::total($from, $to, $currency, $buckets['step']);

        return [
            'series' => [
                'labels' => array_values($buckets['labels']),
                'plan' => array_map(fn($value) => round($value), array_values($plan_series)),
                'fact' => array_map(fn($key) => round($fact['series'][$key] ?? 0.0), array_keys($buckets['labels'])),
            ],
            'plan' => round($plan['amount'], 2),
            'plan_count' => $plan['count'],
            'fact' => round($fact['amount'], 2),
            'fact_count' => $fact['count'],
            'open' => round($plan['open'], 2),
            'open_count' => $plan['open_count'],
            'overdue' => round($plan['overdue'], 2),
            'overdue_count' => $plan['overdue_count'],
            'percent' => $plan['amount'] > 0 ? round($fact['amount'] / $plan['amount'] * 100, 1) : null,
            'diff' => round($fact['amount'] - $plan['amount'], 2),
            'label' => $period['label'],
            'dates' => $period['dates'],
            'symbol' => $ctx->symbol($currency),
            'skipped' => $plan['skipped'] + (int) $fact['skipped'],
        ];
    }
}
