<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Таблица воронки (patch v30): сделки Битрикс24 по стадиям — число и сумма показателя.
 *
 * Данные — DashboardDataService (стадии scopeStatuses, пересчёт в валюту виджета);
 * по умолчанию без фильтра страницы воронки. Сделка считается в стадии, если значение
 * показателя у неё ненулевое; стадии без таких сделок не выводятся.
 */
class FunnelTableWidget extends Widget
{
    /** Показатели: ключ настройки → подпись */
    public const METRICS = [
        'sales' => 'Сделки',
        'licenses' => 'Лицензии',
        'services' => 'Услуги',
        'devcost' => 'Стоимость разработки',
        'platform' => 'Платформенные доработки',
        'services_raw' => 'Услуги без разработки',
    ];

    /** Показатель → метод DashboardDataService */
    public const METHODS = [
        'sales' => 'sales',
        'licenses' => 'licenses',
        'services' => 'services',
        'devcost' => 'devcost',
        'platform' => 'platform',
        'services_raw' => 'servicesRaw',
    ];

    /** Порядок стадий воронки; прочие — в конце по алфавиту */
    public const STAGES = [
        'Lead', 'Research', 'Presentation', 'Pilot project', 'Competition/tender', 'TCP', 'Contracting',
        'Invoice + Specification', 'Execution (PRE-PAYMENT)', 'Execution (POST-PAYMENT)', 'Acceptance tests',
    ];

    /**
     * Стадии, которые считает страница воронки (DashboardDataService::scopeStatuses), в порядке STAGES.
     *
     * В STAGES есть и стадии вне воронки (Execution (PRE-PAYMENT)): их сделки в показатели
     * страницы не входят, поэтому в отборе стадий виджетов воронки их не предлагаем.
     * Execution (PRE-PAYMENT) — оплата уже прошла, сделка по сути завершена (решение владельца
     * 23.09.2026): на неё не рассчитывают, хотя в работе она ещё остаётся.
     *
     * @return string[]
     */
    public static function funnelStages(): array
    {
        $probe = collect(static::STAGES)->map(fn($stage) => (object) ['stage_name' => $stage, 'id' => 0]);

        return DashboardDataService::scopeStatuses($probe)->pluck('stage_name')->values()->all();
    }

    public static function id(): string { return 'funnel_table'; }

    public static function name(): string { return 'Таблица воронки'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Сделки по стадиям воронки: сколько и на какую сумму';
    }

    public static function icon(): string { return 'fa-filter'; }

    public static function sizes(): array { return ['16x8', '8x8', '32x8']; }

    public static function defaultSize(): string { return '16x8'; }

    public static function order(): int { return 100; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель', 'default' => 'sales', 'options' => static::METRICS],
            ['key' => 'filtered', 'type' => 'bool', 'label' => 'С фильтром страницы воронки', 'default' => false],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('dashboard.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // все 10 стадий воронки (funnelStages()): высокому блоку есть чем заполниться;
        // Execution (PRE-PAYMENT) страница воронки не считает — в образце её нет
        $rows = [['Lead', 18, 12.4], ['Research', 11, 9.6], ['Presentation', 7, 8.1], ['Pilot project', 4, 6.9],
            ['Competition/tender', 3, 6.2], ['TCP', 3, 5.8], ['Contracting', 2, 5.4], ['Invoice + Specification', 2, 4.1],
            ['Execution (POST-PAYMENT)', 1, 3.3], ['Acceptance tests', 1, 2.9]];
        $rows = array_map(fn($row) => ['stage' => $row[0], 'count' => $row[1], 'amount' => $row[2] * 1e6], $rows);

        return static::summary(
            $rows, array_sum(array_column($rows, 'amount')), '₽', static::METRICS[$settings['metric']] ?? static::METRICS['sales']
        );
    }

    /**
     * Стадии воронки с числом сделок и суммой показателя
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['stage', 'count', 'amount', 'share', 'avg']], 'total', 'count_total', 'symbol', 'label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $metric = array_key_exists($settings['metric'], static::METHODS) ? $settings['metric'] : 'sales';

        // patch v40: исключение менеджеров — только на странице воронки, стол считает всех
        $result = (new DashboardDataService($currency, (bool) $settings['filtered'], false))->{static::METHODS[$metric]}();

        // servicesRaw кладёт пересчитанное значение сразу в service_raw, остальные — в {field}_RUB
        $property = $metric === 'services_raw' ? 'service_raw' : $result['field'] . '_RUB';

        $stages = [];
        foreach ($result['deals'] as $deal) {
            $value = (float) $deal->{$property};
            if ($value == 0.0) continue;

            $stage = (string) $deal->stage_name;
            $stages[$stage] ??= ['stage' => $stage, 'count' => 0, 'amount' => 0.0];
            $stages[$stage]['count']++;
            $stages[$stage]['amount'] += $value;
        }

        $order = array_flip(static::STAGES);
        uasort($stages, fn($a, $b) => [$order[$a['stage']] ?? PHP_INT_MAX, $a['stage']] <=> [$order[$b['stage']] ?? PHP_INT_MAX, $b['stage']]);

        return static::summary(array_values($stages), (float) $result['amount'], $ctx->symbol($currency), static::METRICS[$metric]);
    }

    /**
     * Доли, средняя сделка и итоги
     *
     * @param array $rows [['stage', 'count', 'amount']]
     * @param float $total
     * @param string $symbol
     * @param string $label
     * @return array
     */
    protected static function summary(array $rows, float $total, string $symbol, string $label): array
    {
        foreach ($rows as &$row) {
            $row['share'] = $total != 0.0 ? round($row['amount'] / $total * 100, 1) : 0.0;
            $row['avg'] = $row['count'] ? $row['amount'] / $row['count'] : null;
        }
        unset($row);

        return [
            'rows' => $rows,
            'total' => $total,
            'count_total' => array_sum(array_column($rows, 'count')),
            'symbol' => $symbol,
            'label' => $label,
        ];
    }
}
