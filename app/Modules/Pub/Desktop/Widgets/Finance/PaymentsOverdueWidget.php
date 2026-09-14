<?php

namespace App\Modules\Pub\Desktop\Widgets\Finance;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\PaymentCalendar\Services\PaymentCalendarService;
use App\Modules\Pub\User\Models\User;

/**
 * Просроченные оплаты (patch v30) — плановая дата прошла, факта нет.
 *
 * Отбор — PaymentCalendarService::rows() с отбором платёжного календаря по умолчанию
 * (спецификации в работе плюс уже оплаченные) и состоянием overdue: отменённые
 * спецификации просрочкой не считаются, как и на странице календаря.
 *
 * Сумма — план по текущему курсу; платежи, для которых курса нет, не суммируются,
 * а попадают в skipped. Корзины давности — PaymentCalendarService::ages().
 */
class PaymentsOverdueWidget extends Widget
{
    /** Сколько строк держим в данных — вьюха режет их по высоте блока */
    public const MAX_ROWS = 30;

    public static function id(): string
    {
        return 'payments_overdue';
    }

    public static function name(): string
    {
        return 'Просроченные оплаты';
    }

    public static function category(): string
    {
        return 'finance';
    }

    public static function description(): string
    {
        return 'План прошёл, денег нет: сумма просрочки и самые крупные должники';
    }

    public static function icon(): string
    {
        return 'fa-triangle-exclamation';
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
        return 120;
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
            ['key' => 'age', 'type' => 'select', 'label' => 'Давность', 'default' => 'all', 'hint' => 'Корзины давности платёжного календаря',
                'options' => fn() => ['all' => 'Любая'] + collect(PaymentCalendarService::ages())
                    ->map(fn($age) => $age['label'])
                    ->all()],
            // по умолчанию — сколько влезет по высоте блока (не больше MAX_ROWS)
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько платежей в списке', 'default' => self::MAX_ROWS, 'min' => 1, 'max' => self::MAX_ROWS],
            ['key' => 'partner', 'type' => 'select', 'label' => 'Партнёр', 'default' => 'all',
                'options' => fn() => ['all' => 'Все партнёры'] + PaymentCalendarService::partners()
                    ->mapWithKeys(fn($partner) => [(string) $partner->id => (string) $partner->name])
                    ->all()],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('payment_calendar.index', array_filter([
            'state' => 'overdue',
            'age' => ($settings['age'] ?? 'all') !== 'all' ? (string) $settings['age'] : null,
            'partner' => ($settings['partner'] ?? 'all') !== 'all' ? (int) $settings['partner'] : null,
            // просрочка прошлых лет в отбор по году не попадает
            'all_years' => 1,
        ]));
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // образец на MAX_ROWS платежей — чтобы высокий блок было чем заполнить
        $companies = ['ООО «Гранит»', 'RASHMI', 'АО «Вектор»', 'ООО «Северсталь-Инфо»', 'ГК Восток', 'МТ Интеграция',
            'ООО «Телеком-Мастер»', 'Риверсистемс', 'Digital Wave Ltd', 'СТК', 'ИнфоТех Сервис', 'Shanghai Hengde Science & Tech Co'];
        $specs = ['С.3 Лицензии', 'С.1 (часть 2)', 'С.2 Внедрение', 'С.4 Поддержка на 2026 год', 'С.5 Доработки', 'С.1 Оборудование'];
        $ages = PaymentCalendarService::ages();

        $rows = [];
        $buckets = [];
        for ($i = 0; $i < static::MAX_ROWS; $i++) {
            $days = 3 + $i * 13;
            $amount = (($i * 29) % 40 + 1) * 52000.0;
            $code = PaymentCalendarService::age($days);

            $buckets[$code] ??= ['code' => $code, 'label' => $ages[$code]['label'] ?? $code, 'count' => 0, 'amount' => 0.0];
            $buckets[$code]['count']++;
            $buckets[$code]['amount'] += $amount;

            $rows[] = [
                'company' => $companies[$i % count($companies)], 'spec' => $specs[$i % count($specs)], 'partner' => 'ГК Восток',
                'date' => now()->subDays($days)->format('d.m.Y'), 'days' => $days,
                'amount' => $amount, 'age' => $code, 'url' => null,
            ];
        }

        // как в data(): самые крупные сверху, корзины в порядке давности
        usort($rows, fn($a, $b) => $b['amount'] <=> $a['amount']);
        $ordered = [];
        foreach (array_keys($ages) as $code) {
            if (isset($buckets[$code])) $ordered[] = $buckets[$code];
        }

        return [
            'amount' => array_sum(array_column($rows, 'amount')), 'count' => count($rows),
            'max_days' => 3 + (static::MAX_ROWS - 1) * 13, 'skipped' => 0, 'symbol' => '₽',
            'buckets' => $ordered,
            'rows' => $rows,
        ];
    }

    /**
     * Сумма просрочки, корзины давности и самые крупные платежи
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['amount', 'count', 'max_days', 'skipped', 'symbol', 'buckets', 'rows']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $partner = (string) ($settings['partner'] ?? 'all');
        $age = (string) ($settings['age'] ?? 'all');

        $rows = PaymentCalendarService::rows([
            'spec_status' => PaymentCalendarService::SPEC_STATUS_DEFAULT,
            'partner' => $partner !== 'all' ? [(int) $partner] : [],
            'state' => ['overdue'],
            'age' => $age !== 'all' ? [$age] : [],
        ]);

        $ages = PaymentCalendarService::ages();
        $buckets = [];
        $list = [];
        $total = ['amount' => 0.0, 'count' => 0, 'skipped' => 0, 'max_days' => 0];

        foreach ($rows as $row) {
            // просрочен — значит факта нет: считаем план по текущему курсу
            $amount = CurrencyService::convertAmount((float) $row->amount_plan, $row->currency_slug, $currency, now());

            if ($amount === null) {
                $total['skipped']++;
                continue;
            }

            $days = (int) $row->overdue_days;
            $code = (string) $row->age;

            $total['amount'] += $amount;
            $total['count']++;
            $total['max_days'] = max($total['max_days'], $days);

            $buckets[$code] ??= ['code' => $code, 'label' => $ages[$code]['label'] ?? $code, 'count' => 0, 'amount' => 0.0];
            $buckets[$code]['count']++;
            $buckets[$code]['amount'] += $amount;

            $list[] = [
                'company' => (string) ($row->company_name ?: '—'),
                'spec' => (string) ($row->spec_name ?: '—'),
                'partner' => (string) ($row->partner_name ?: ''),
                'date' => $row->date_plan?->format('d.m.Y'),
                'days' => $days,
                'amount' => round($amount, 2),
                'age' => $code,
                // строка ведёт в календарь, отфильтрованный по этой спецификации
                'url' => route('payment_calendar.index', ['spec' => (int) $row->spec_id, 'all_years' => 1]),
            ];
        }

        // самые крупные — сверху; корзины в порядке давности
        usort($list, fn($a, $b) => $b['amount'] <=> $a['amount']);

        $ordered = [];
        foreach (array_keys($ages) as $code) {
            if (isset($buckets[$code])) $ordered[] = $buckets[$code];
        }

        return [
            'amount' => round($total['amount'], 2),
            'count' => $total['count'],
            'max_days' => $total['max_days'],
            'skipped' => $total['skipped'],
            'symbol' => $ctx->symbol($currency),
            'buckets' => $ordered,
            'rows' => array_slice($list, 0, static::MAX_ROWS),
        ];
    }
}
