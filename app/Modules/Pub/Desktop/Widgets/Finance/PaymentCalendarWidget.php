<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\PaymentCalendar\Services\PaymentCalendarService;
use App\Modules\Pub\User\Models\User;

/**
 * Платёжный календарь (patch v30) — ближайшие плановые платежи по датам:
 * дата, компания со спецификацией и сумма.
 *
 * Отбор — PaymentCalendarService::rows() с отбором платёжного календаря по умолчанию
 * (спецификации в работе плюс уже оплаченные платежи): состояния soon и planned
 * с датой плана в горизонте, по желанию — просроченные сверху. Отменённые
 * спецификации и платежи без даты в календарь не попадают, как и на странице.
 *
 * Суммы — план по текущему курсу; платежи, для которых курса нет, не суммируются,
 * а попадают в skipped.
 */
class PaymentCalendarWidget extends Widget
{
    /** Сколько строк держим в данных — вьюха режет их по высоте блока */
    public const MAX_ROWS = 40;

    public static function id(): string
    {
        return 'payment_calendar';
    }

    public static function name(): string
    {
        return 'Платёжный календарь';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'Ближайшие плановые платежи по датам: дата, компания, спецификация и сумма';
    }

    public static function icon(): string
    {
        return 'fa-calendar-check';
    }

    public static function sizes(): array
    {
        return ['8x4', '16x8', '8x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 130;
    }

    public static function usesCurrency(): bool
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
            ['key' => 'days', 'type' => 'number', 'label' => 'Горизонт, дней', 'default' => PaymentCalendarService::SOON_DAYS, 'min' => 1, 'max' => 365,
                'hint' => 'Сколько дней вперёд показывать; «скоро» на странице — константа payment_soon_days'],
            ['key' => 'overdue', 'type' => 'bool', 'label' => 'Показывать просроченные сверху', 'default' => true],
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'options' => fn() => ['all' => 'Все партнёры'] + PaymentCalendarService::partners()
                    ->mapWithKeys(fn($partner) => [(string) $partner->id => (string) $partner->name])
                    ->all()],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $states = ['soon', 'planned'];
        if (!empty($settings['overdue'])) $states[] = 'overdue';

        return route('payment_calendar.index', array_filter([
            'state' => $states,
            'partner' => ($settings['partner'] ?? 'all') !== 'all' ? (int) $settings['partner'] : null,
            'all_years' => 1,
        ]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // образец на MAX_ROWS строк — чтобы высокий блок было чем заполнить:
        // три просроченных сверху, дальше платежи равномерно по горизонту
        $companies = ['ООО «Гранит»', 'АО «Вектор»', 'ООО «Альфа»', 'RASHMI', 'ООО «Северсталь-Инфо»', 'ГК Восток',
            'МТ Интеграция', 'ООО «Телеком-Мастер»', 'Риверсистемс', 'Digital Wave Ltd', 'СТК', 'ИнфоТех Сервис'];
        $specs = ['С.3 Лицензии', 'С.2 Внедрение', 'С.1 Оборудование', 'С.1 (часть 2)', 'С.4 Поддержка на 2026 год', 'С.5 Доработки'];
        $horizon = max(1, (int) $settings['days']);
        $states = PaymentCalendarService::states();

        $rows = [];
        $total = ['amount' => 0.0, 'count' => 0, 'overdue_amount' => 0.0, 'overdue_count' => 0];
        for ($i = 0; $i < static::MAX_ROWS; $i++) {
            $days = $i < 3 ? -20 + $i * 7 : intdiv($horizon * ($i - 3), static::MAX_ROWS - 3);
            $state = $days < 0 ? 'overdue' : ($days <= PaymentCalendarService::SOON_DAYS ? 'soon' : 'planned');
            $amount = (($i * 53) % 40 + 2) * 45000.0;
            $date = now()->addDays($days);

            if ($state === 'overdue') {
                $total['overdue_amount'] += $amount;
                $total['overdue_count']++;
            } else {
                $total['amount'] += $amount;
                $total['count']++;
            }

            $rows[] = [
                'company' => $companies[$i % count($companies)], 'spec' => $specs[$i % count($specs)], 'partner' => 'ГК Восток',
                'date' => $date->format('d.m.Y'), 'date_short' => $date->format('d.m'),
                'weekday' => $date->locale('ru')->isoFormat('dd'),
                'amount' => $amount, 'days' => $days, 'state' => $state,
                'label' => $states[$state]['label'], 'color' => $states[$state]['color'],
                'url' => null,
            ];
        }

        return [
            'rows' => $rows, 'amount' => $total['amount'], 'count' => $total['count'],
            'overdue_amount' => $total['overdue_amount'], 'overdue_count' => $total['overdue_count'],
            'days' => $horizon, 'symbol' => '₽', 'skipped' => 0,
        ];
    }

    /**
     * Ближайшие плановые платежи и итоги по горизонту
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows', 'amount', 'count', 'overdue_amount', 'overdue_count', 'days', 'symbol', 'skipped']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $days = (int) $settings['days'];
        $partner = (string) ($settings['partner'] ?? 'all');
        $limit = now()->addDays($days)->endOfDay();

        $states = ['soon', 'planned'];
        if (!empty($settings['overdue'])) $states[] = 'overdue';

        // строки уже отсортированы по дате: просроченные оказываются сверху
        $rows = PaymentCalendarService::rows([
            'spec_status' => PaymentCalendarService::SPEC_STATUS_DEFAULT,
            'partner' => $partner !== 'all' ? [(int) $partner] : [],
            'state' => $states,
        ])->filter(fn($row) => $row->date_plan && ($row->state === 'overdue' || $row->date_plan <= $limit));

        $states_decorate = PaymentCalendarService::states();
        $list = [];
        $total = ['amount' => 0.0, 'count' => 0, 'overdue_amount' => 0.0, 'overdue_count' => 0, 'skipped' => 0];

        foreach ($rows as $row) {
            // факта у этих платежей нет: считаем план по текущему курсу
            $amount = CurrencyService::convertAmount((float) $row->amount_plan, $row->currency_slug, $currency, now());

            if ($amount === null) {
                $total['skipped']++;
                continue;
            }

            if ($row->state === 'overdue') {
                $total['overdue_amount'] += $amount;
                $total['overdue_count']++;
            } else {
                $total['amount'] += $amount;
                $total['count']++;
            }

            $list[] = [
                'company' => (string) ($row->company_name ?: '—'),
                'spec' => (string) ($row->spec_name ?: '—'),
                'partner' => (string) ($row->partner_name ?: ''),
                'date' => $row->date_plan->format('d.m.Y'),
                'date_short' => $row->date_plan->format('d.m'),
                'weekday' => $row->date_plan->locale('ru')->isoFormat('dd'),
                'amount' => round($amount, 2),
                'days' => (int) $row->days_left,
                'state' => (string) $row->state,
                'label' => $states_decorate[$row->state]['label'] ?? '',
                'color' => $states_decorate[$row->state]['color'] ?? 'secondary',
                // строка ведёт в календарь, отфильтрованный по этой спецификации
                'url' => route('payment_calendar.index', ['spec' => (int) $row->spec_id, 'all_years' => 1]),
            ];
        }

        return [
            'rows' => array_slice($list, 0, static::MAX_ROWS),
            'amount' => round($total['amount'], 2),
            'count' => $total['count'],
            'overdue_amount' => round($total['overdue_amount'], 2),
            'overdue_count' => $total['overdue_count'],
            'days' => $days,
            'symbol' => $ctx->symbol($currency),
            'skipped' => $total['skipped'],
        ];
    }
}
