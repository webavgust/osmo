<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Сделки по кварталам плана (patch v30): суммы сделок по плановому кварталу
 * (поле сделки uf_crm_1722255711522) столбцами, рядом — тот же квартал прошлого года.
 *
 * Данные — DashboardDataService::sales() (стадии воронки, пересчёт в валюту виджета)
 * либо весь реестр CrmDealRepository::getFiltered(false), смотря что выбрано в
 * настройке «Статусы»; фильтр страницы воронки не применяется никогда. Отменённые
 * сделки (семантика стадии F — «Canceled») в план не идут ни в одном отборе.
 * Сделки, у которых квартал не выбран, в столбцы не
 * попадают — они считаются отдельно и показываются предупреждением под графиком:
 * года у такой сделки нет, и сравнивать её с прошлым годом не с чем. Сделки без
 * суммы в предупреждение не идут — терять в них нечего (лиды и ранние стадии).
 */
class DealsQuarterWidget extends Widget
{
    /** Сдвиг года относительно текущего */
    public const YEARS = [
        '0' => 'Текущий год',
        '1' => 'Следующий год',
        '-1' => 'Прошлый год',
    ];

    /** Подписи кварталов */
    public const QUARTERS = [1 => 'I кв.', 2 => 'II кв.', 3 => 'III кв.', 4 => 'IV кв.'];

    /**
     * Какие сделки считать.
     *
     * «Стадии воронки» — открытые сделки, как на странице воронки; но у них плановый
     * квартал почти всегда будущий, и сравнивать с прошлым годом оказывается не с чем,
     * поэтому есть и отбор по всем стадиям. Отменённые сделки — не план, их сумма
     * раздувала год (аудит 23.09: 118 млн из 1 млрд в 2026-м), поэтому «все» — без них.
     */
    public const SCOPE = [
        'funnel' => 'Стадии воронки',
        'all' => 'Все, кроме отменённых',
        'closed' => 'Только завершённые',
    ];

    /**
     * Стадии завершённых сделок. Execution (PRE-PAYMENT) — сделка попадает туда, когда оплата уже
     * прошла: на неё уже не рассчитывают, всё случилось, хотя в работе она ещё остаётся
     * (решение владельца 23.09.2026) — считается завершённой
     */
    public const CLOSED = ['Completed', 'Closing documents', 'Execution (PRE-PAYMENT)'];

    /** Семантика стадии Битрикса «провал» (отменённые сделки) */
    public const SEMANTIC_FAIL = 'F';

    public static function id(): string { return 'deals_quarter'; }

    public static function name(): string { return 'Сделки по кварталам'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Суммы сделок по плановым кварталам и сравнение с прошлым годом';
    }

    public static function icon(): string { return 'fa-chart-column'; }

    public static function sizes(): array { return ['8x4', '16x4', '16x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 450; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'year', 'type' => 'select', 'label' => 'Год', 'default' => '0', 'options' => static::YEARS],
            ['key' => 'compare', 'type' => 'bool', 'label' => 'Сравнить с прошлым годом', 'default' => true],
            ['key' => 'scope', 'type' => 'select', 'label' => 'Статусы', 'default' => 'all', 'options' => static::SCOPE,
                'hint' => 'У сделок воронки плановый квартал обычно будущий — сравнивать с прошлым годом не с чем'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('dashboard.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $year = now()->year + (int) $settings['year'];
        $now = [58.2e6, 71.4e6, 96.8e6, 44.1e6];
        $was = [49.7e6, 63.0e6, 78.5e6, 61.3e6];
        $counts = [14, 31, 49, 29];

        $quarters = array_map(fn($i) => [
            'key' => $year . 'q' . ($i + 1),
            'label' => static::QUARTERS[$i + 1],
            'amount' => $now[$i],
            'count' => $counts[$i],
            'prev_amount' => $was[$i],
            'prev_count' => $counts[$i] - 3,
        ], range(0, 3));

        return static::shape($quarters, $year, 79, 118.6e6, '₽', (bool) $settings['compare']);
    }

    /**
     * Суммы сделок по плановым кварталам года
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['quarters' => [['key', 'label', 'amount', 'count', 'prev_amount', 'prev_count']], 'year', 'prev_year', 'total', 'prev_total', 'count_total', 'none_count', 'none_amount', 'symbol', 'compare']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $year = now()->year + (int) $settings['year'];
        $field = DealProjectService::ufQuarter();

        [$deals, $value] = static::dealsOf((string) $settings['scope'], $currency);

        $sums = [];
        $none_count = 0;
        $none_amount = 0.0;

        foreach ($deals as $deal) {
            $amount = $value($deal);
            $quarter = trim((string) ($deal->{$field} ?? ''));

            // в Битриксе плановый квартал хранится как «2026q3»; всё прочее («не выбрано») — мимо столбцов
            if (!preg_match('/^(\d{4})q([1-4])$/', $quarter, $match)) {
                if ($amount == 0.0) continue;
                $none_count++;
                $none_amount += $amount;
                continue;
            }

            $key = $match[1] . 'q' . $match[2];
            $sums[$key] ??= ['amount' => 0.0, 'count' => 0];
            $sums[$key]['amount'] += $amount;
            $sums[$key]['count']++;
        }

        $quarters = array_map(function ($number) use ($sums, $year) {
            $key = $year . 'q' . $number;
            $prev = ($year - 1) . 'q' . $number;

            return [
                'key' => $key,
                'label' => static::QUARTERS[$number],
                'amount' => $sums[$key]['amount'] ?? 0.0,
                'count' => $sums[$key]['count'] ?? 0,
                'prev_amount' => $sums[$prev]['amount'] ?? 0.0,
                'prev_count' => $sums[$prev]['count'] ?? 0,
            ];
        }, [1, 2, 3, 4]);

        return static::shape($quarters, $year, $none_count, $none_amount, $ctx->symbol($currency), (bool) $settings['compare']);
    }

    /**
     * Сделки отбора и способ достать из них сумму в валюте виджета.
     *
     * У стадий воронки суммы считает DashboardDataService (как на странице воронки),
     * у остальных отборов — те же текущие курсы, но по сырому полю сделки.
     *
     * @param string $scope ключ SCOPE
     * @param string $currency
     * @return array [коллекция сделок, callable(deal): float]
     */
    protected static function dealsOf(string $scope, string $currency): array
    {
        if ($scope === 'funnel') {
            $result = (new DashboardDataService($currency, false))->sales();

            return [$result['deals'], fn($deal) => (float) $deal->opportunity_RUB];
        }

        $rates = CurrencyService::getConvertRates();
        $deals = CrmDealRepository::getFiltered(false);

        // отменённые сделки — не план
        $deals = $deals->where('stage_semantic_id', '!=', static::SEMANTIC_FAIL);

        if ($scope === 'closed') {
            $deals = $deals->whereIn('stage_name', static::CLOSED);
        }

        return [$deals, fn($deal) => (float) $deal->opportunity * (float) ($rates[(string) $deal->currency_id][$currency] ?? 0.0)];
    }

    /**
     * Итоги и служебные поля
     *
     * @param array $quarters
     * @param int $year
     * @param int $none_count сделки без планового квартала
     * @param float $none_amount
     * @param string $symbol
     * @param bool $compare
     * @return array
     */
    protected static function shape(array $quarters, int $year, int $none_count, float $none_amount, string $symbol, bool $compare): array
    {
        $total = array_sum(array_column($quarters, 'amount'));
        $prev_total = array_sum(array_column($quarters, 'prev_amount'));
        $max = max(array_merge([0.0], array_column($quarters, 'amount'), $compare ? array_column($quarters, 'prev_amount') : []));

        foreach ($quarters as &$quarter) {
            $quarter['bar'] = $max > 0 ? round($quarter['amount'] / $max * 100, 1) : 0.0;
            $quarter['prev_bar'] = $max > 0 ? round($quarter['prev_amount'] / $max * 100, 1) : 0.0;
        }
        unset($quarter);

        return [
            'quarters' => $quarters,
            'year' => $year,
            'prev_year' => $year - 1,
            'total' => $total,
            'prev_total' => $prev_total,
            'count_total' => array_sum(array_column($quarters, 'count')),
            'none_count' => $none_count,
            'none_amount' => $none_amount,
            'symbol' => $symbol,
            'compare' => $compare,
        ];
    }
}
