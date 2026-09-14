<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Воронка по стадиям (patch v30): стадии Lead → Acceptance tests полосами —
 * длина полосы это сумма показателя, подпись — число сделок на стадии.
 *
 * Данные — DashboardDataService (стадии scopeStatuses, пересчёт в валюту виджета),
 * всегда без фильтра страницы воронки: стол не должен зависеть от чужого отбора.
 *
 * В отличие от таблицы воронки (FunnelTableWidget) сделки с нулевым значением
 * показателя из счётчика не выбрасываются: воронка показывает, сколько сделок
 * стоит на стадии, даже если сумма у них ещё не заполнена (Lead, Research).
 */
class FunnelStagesWidget extends Widget
{
    public static function id(): string { return 'funnel_stages'; }

    public static function name(): string { return 'Воронка по стадиям'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Стадии воронки полосами: сумма и число сделок на каждой';
    }

    public static function icon(): string { return 'fa-filter-circle-dollar'; }

    public static function sizes(): array { return ['8x4', '4x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 150; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель', 'default' => 'sales', 'options' => FunnelTableWidget::METRICS],
            ['key' => 'stages', 'type' => 'list', 'label' => 'Стадии', 'default' => [],
                'options' => array_combine(FunnelTableWidget::STAGES, FunnelTableWidget::STAGES),
                'hint' => 'Пусто — все стадии воронки'],
            ['key' => 'counts', 'type' => 'bool', 'label' => 'Показывать количество сделок', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('dashboard.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $rows = [['Lead', 18, 0], ['Research', 11, 9.6], ['Presentation', 7, 8.1], ['Pilot project', 4, 6.9],
            ['TCP', 3, 5.8], ['Contracting', 2, 5.4], ['Acceptance tests', 1, 2.9]];

        $rows = array_map(fn($row) => ['stage' => $row[0], 'count' => $row[1], 'amount' => $row[2] * 1e6], $rows);

        return static::shape($rows, '₽', FunnelTableWidget::METRICS[$settings['metric']] ?? FunnelTableWidget::METRICS['sales']);
    }

    /**
     * Стадии воронки: число сделок и сумма показателя
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['stage', 'count', 'amount', 'share', 'bar']], 'total', 'count_total', 'symbol', 'label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $metric = array_key_exists($settings['metric'], FunnelTableWidget::METHODS) ? $settings['metric'] : 'sales';
        $only = array_map('strval', (array) $settings['stages']);

        $result = (new DashboardDataService($currency, false))->{FunnelTableWidget::METHODS[$metric]}();

        // servicesRaw кладёт пересчитанное значение сразу в service_raw, остальные — в {field}_RUB
        $property = $metric === 'services_raw' ? 'service_raw' : $result['field'] . '_RUB';

        $stages = [];
        foreach ($result['deals'] as $deal) {
            $stage = (string) $deal->stage_name;
            if ($only && !in_array($stage, $only, true)) continue;

            $stages[$stage] ??= ['stage' => $stage, 'count' => 0, 'amount' => 0.0];
            $stages[$stage]['count']++;
            $stages[$stage]['amount'] += (float) $deal->{$property};
        }

        $order = array_flip(FunnelTableWidget::STAGES);
        uasort($stages, fn($a, $b) => [$order[$a['stage']] ?? PHP_INT_MAX, $a['stage']] <=> [$order[$b['stage']] ?? PHP_INT_MAX, $b['stage']]);

        return static::shape(array_values($stages), $ctx->symbol($currency), FunnelTableWidget::METRICS[$metric]);
    }

    /**
     * Доли и длина полос.
     *
     * Полоса меряется от самой крупной стадии, чтобы воронку было видно;
     * подпись доли — от всей суммы. Если суммы нет ни у одной стадии
     * (в начале воронки так бывает), полосы считаются по числу сделок.
     *
     * @param array $rows [['stage', 'count', 'amount']]
     * @param string $symbol
     * @param string $label
     * @return array
     */
    protected static function shape(array $rows, string $symbol, string $label): array
    {
        $total = array_sum(array_column($rows, 'amount'));
        $max = $rows ? max(array_map('abs', array_column($rows, 'amount'))) : 0.0;
        $max_count = $rows ? max(array_column($rows, 'count')) : 0;

        foreach ($rows as &$row) {
            $row['share'] = $total != 0.0 ? round($row['amount'] / $total * 100, 1) : 0.0;
            $row['bar'] = match (true) {
                $max > 0 => round(abs($row['amount']) / $max * 100, 1),
                $max_count > 0 => round($row['count'] / $max_count * 100, 1),
                default => 0.0,
            };
        }
        unset($row);

        return [
            'rows' => $rows,
            'total' => $total,
            'count_total' => array_sum(array_column($rows, 'count')),
            'by_count' => $max <= 0,
            'symbol' => $symbol,
            'label' => $label,
        ];
    }
}
