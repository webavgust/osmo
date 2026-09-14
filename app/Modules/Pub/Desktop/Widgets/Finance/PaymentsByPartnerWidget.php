<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\PaymentCalendar\Services\PaymentCalendarService;
use App\Modules\Pub\User\Models\User;

/**
 * Оплаты по партнёрам (patch v30) — кто принёс больше денег за период стола
 * и какая у него доля от общего.
 *
 * Отбор — PaymentCalendarService::rows() с отбором платёжного календаря по умолчанию
 * (спецификации в работе плюс уже оплаченные). Факт — платежи с датой поступления
 * внутри периода, как у виджета «Оплаты за период»; план — платежи с плановой датой
 * внутри периода, отменённые спецификации в план не идут.
 *
 * Факт пересчитывается по курсу на дату поступления, план — по текущему;
 * платежи без курса не суммируются, а попадают в skipped.
 *
 * Партнёр берётся из рамочного договора спецификации; платежи по спецификациям
 * без договора собираются в строку «Без партнёра» — прятать деньги нельзя.
 */
class PaymentsByPartnerWidget extends Widget
{
    /** Сколько строк держим в данных — вьюха режет их по высоте блока */
    public const MAX_ROWS = 20;

    public static function id(): string
    {
        return 'payments_by_partner';
    }

    public static function name(): string
    {
        return 'Оплаты по партнёрам';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Кто принёс больше денег за период и какая у него доля от общего';
    }

    public static function icon(): string
    {
        return 'fa-hand-holding-dollar';
    }

    public static function sizes(): array
    {
        return ['8x4', '8x8', '16x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 180;
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
            ['key' => 'mode', 'type' => 'select', 'label' => 'Показатель', 'default' => 'fact',
                'hint' => 'Факт — по дате поступления, план — по плановой дате платежа',
                'options' => ['fact' => 'Фактические поступления', 'plan' => 'Плановые платежи']],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько партнёров в списке', 'default' => 8, 'min' => 1, 'max' => self::MAX_ROWS],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // период стола в отбор календаря не переносится — ведём на состояния за все годы
        return route('payment_calendar.index', [
            'state' => (string) ($settings['mode'] ?? 'fact') === 'fact' ? ['paid'] : ['overdue', 'soon', 'planned'],
            'all_years' => 1,
        ]);
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $sample = [
            ['ГК Восток', 4820000.0, 9],
            ['МТ Интеграция', 2310000.0, 4],
            ['Shanghai Hengde', 1460000.0, 3],
            ['АО «Вектор»', 620000.0, 2],
        ];

        $total = 9210000.0;
        $rows = [];
        foreach ($sample as [$name, $amount, $count]) {
            $rows[] = [
                'name' => $name, 'partner' => null, 'amount' => $amount, 'count' => $count,
                'share' => round($amount / $total * 100, 1), 'url' => null,
            ];
        }

        return [
            'rows' => $rows,
            'total' => $total, 'count' => 18, 'partners' => 4,
            'others' => 0.0, 'others_count' => 0,
            'mode' => 'fact', 'label' => 'Текущий квартал', 'dates' => '01.07.2026 – 30.09.2026',
            'symbol' => '₽', 'skipped' => 0,
        ];
    }

    /**
     * Партнёры по сумме оплат за период
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows', 'total', 'count', 'partners', 'others', 'others_count', 'mode', 'label', 'dates', 'symbol', 'skipped']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $period = $ctx->periodFor($settings);
        [$from, $to] = [$period['from'], $period['to']];
        $fact = (string) ($settings['mode'] ?? 'fact') === 'fact';

        $rows = PaymentCalendarService::rows(['spec_status' => PaymentCalendarService::SPEC_STATUS_DEFAULT])
            ->filter(fn($row) => $fact
                ? ($row->date_fact && $row->date_fact->between($from, $to))
                : ($row->date_plan && $row->state !== 'canceled' && $row->date_plan->between($from, $to)));

        $partners = [];
        $total = ['amount' => 0.0, 'count' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            // факт — по курсу на дату поступления, план — по текущему
            $amount = $fact
                ? CurrencyService::convertAmount((float) $row->amount_fact, $row->currency_slug, $currency, $row->date_fact)
                : CurrencyService::convertAmount((float) $row->amount_plan, $row->currency_slug, $currency, now());

            if ($amount === null) {
                $total['skipped']++;
                continue;
            }

            $id = (int) ($row->partner_id ?: 0);

            $partners[$id] ??= [
                'name' => (string) ($row->partner_name ?: 'Без партнёра'),
                'partner' => $id ?: null,
                'amount' => 0.0,
                'count' => 0,
                'url' => $id ? route('partner.detail', $id) : null,
            ];

            $partners[$id]['amount'] += $amount;
            $partners[$id]['count']++;

            $total['amount'] += $amount;
            $total['count']++;
        }

        // самые денежные — сверху
        $list = array_values($partners);
        usort($list, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $limit = max(1, min((int) ($settings['limit'] ?? 8), static::MAX_ROWS));
        $top = array_slice($list, 0, $limit);
        $others = array_slice($list, $limit);

        foreach ($top as &$row) {
            $row['amount'] = round($row['amount'], 2);
            $row['share'] = $total['amount'] > 0 ? round($row['amount'] / $total['amount'] * 100, 1) : 0.0;
        }
        unset($row);

        return [
            'rows' => $top,
            'total' => round($total['amount'], 2),
            'count' => $total['count'],
            'partners' => count($list),
            'others' => round(array_sum(array_column($others, 'amount')), 2),
            'others_count' => count($others),
            'mode' => $fact ? 'fact' : 'plan',
            'label' => $period['label'],
            'dates' => $period['dates'],
            'symbol' => $ctx->symbol($currency),
            'skipped' => $total['skipped'],
        ];
    }
}
