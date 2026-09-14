<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Carbon\Carbon;

/**
 * Страны × статусы помесячно (patch v30): суммы сделок по плановому месяцу —
 * как таблица «Страны и статусы помесячно» на странице воронки.
 *
 * Данные — DashboardDataService::country_status_month() (все сделки без фильтра
 * страницы, пересчёт в валюту виджета); в кэш кладутся только суммы.
 */
class CountryMonthWidget extends Widget
{
    /** Короткие подписи месяцев */
    public const MONTHS = [1 => 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен', 'окт', 'ноя', 'дек'];

    public static function id(): string { return 'country_month'; }

    public static function name(): string { return 'Страны × статусы помесячно'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Суммы сделок по странам и статусам на ближайшие месяцы';
    }

    public static function icon(): string { return 'fa-table-cells'; }

    public static function sizes(): array { return ['16x8', '32x8', '32x12']; }

    public static function defaultSize(): string { return '32x8'; }

    public static function order(): int { return 200; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'months', 'type' => 'select', 'label' => 'Месяцев', 'default' => '6', 'options' => ['3' => '3', '6' => '6', '12' => '12']],
            ['key' => 'rows', 'type' => 'select', 'label' => 'Строки', 'default' => 'country_status', 'options' => ['country_status' => 'Страна → статус', 'country' => 'Только страны']],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('dashboard.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $keys = array_column(static::columns((int) $settings['months']), 'key');
        $matrix = [];

        // до ~40 строк: высоким блокам должно быть чем заполниться
        $countries = ['Азербайджан', 'Армения', 'Беларусь', 'Грузия', 'Казахстан', 'Киргизия', 'Россия', 'Узбекистан'];
        foreach ($countries as $c => $country) {
            foreach (['Presentation', 'TCP', 'Contracting', 'Invoice + Specification', 'Execution (PRE-PAYMENT)'] as $s => $status) {
                foreach ($keys as $m => $key) {
                    $k = ($c + 2) * ($s + 3) + $m * 5;
                    if ($k % 4 !== 0) $matrix[$country][$status][$key] = ($k % 9 + 1) * 850000.0;
                }
            }
        }

        return static::table($matrix, (int) $settings['months'], $settings['rows'], '₽');
    }

    /**
     * Матрица сумм без моделей
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['columns', 'rows', 'col_totals', 'grand_total', 'max_cell', 'symbol']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $months = (int) $settings['months'];
        $result = (new DashboardDataService($currency, false))->country_status_month($months);

        $matrix = [];
        foreach ($result['matrix'] as $country => $statuses) {
            foreach ($statuses as $status => $cells) {
                foreach ($cells as $month => $cell) {
                    $key = sprintf('%02d', (int) $month);
                    $matrix[(string) $country][(string) $status][$key] = ($matrix[(string) $country][(string) $status][$key] ?? 0.0) + (float) $cell['amount'];
                }
            }
        }

        return static::table($matrix, $months, $settings['rows'], $ctx->symbol($currency));
    }

    /**
     * Колонки-месяцы от текущего: ['key' => '09', 'label' => 'сен', 'title' => '09.2026']
     *
     * @param int $months
     * @return array
     */
    public static function columns(int $months): array
    {
        $start = Carbon::now()->startOfMonth();

        return array_map(function ($i) use ($start) {
            $date = $start->copy()->addMonths($i);

            return ['key' => $date->format('m'), 'label' => static::MONTHS[$date->month], 'title' => $date->format('m.Y')];
        }, range(0, max(1, $months) - 1));
    }

    /**
     * Строки, итоги и максимум ячейки
     *
     * @param array $matrix страна → статус → месяц ('09') → сумма
     * @param int $months
     * @param string $mode country_status | country
     * @param string $symbol
     * @return array
     */
    protected static function table(array $matrix, int $months, string $mode, string $symbol): array
    {
        $columns = static::columns($months);
        $keys = array_column($columns, 'key');
        $order = array_flip(FunnelTableWidget::STAGES);
        ksort($matrix);

        $rows = [];
        foreach ($matrix as $country => $statuses) {
            uksort($statuses, fn($a, $b) => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);
            $lines = $statuses;

            // «Только страны»: суммы по всем статусам страны (ключи '10'–'12' PHP делает целыми — складываем явно)
            if ($mode === 'country') {
                $sum = [];
                foreach ($statuses as $cells) {
                    foreach ($cells as $key => $amount) {
                        $sum[$key] = ($sum[$key] ?? 0.0) + (float) $amount;
                    }
                }
                $lines = ['' => $sum];
            }

            $first = true;
            foreach ($lines as $status => $cells) {
                $row = ['country' => $country, 'status' => (string) $status, 'first' => $first, 'cells' => [], 'total' => 0.0];
                foreach ($keys as $key) {
                    $row['cells'][$key] = (float) ($cells[$key] ?? 0.0);
                    $row['total'] += $row['cells'][$key];
                }
                $rows[] = $row;
                $first = false;
            }
        }

        $col_totals = [];
        foreach ($keys as $key) {
            $col_totals[$key] = array_sum(array_map(fn($row) => $row['cells'][$key], $rows));
        }
        $cells = array_merge([], ...array_map(fn($row) => array_values($row['cells']), $rows));

        return [
            'columns' => $columns,
            'rows' => $rows,
            'col_totals' => $col_totals,
            'grand_total' => array_sum($col_totals),
            'max_cell' => $cells ? max(array_map('abs', $cells)) : 0.0,
            'symbol' => $symbol,
        ];
    }
}
