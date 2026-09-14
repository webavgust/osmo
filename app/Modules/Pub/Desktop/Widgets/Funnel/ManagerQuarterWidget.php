<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Proposal\Services\ProposalDealService;
use Illuminate\Support\Str;

/**
 * Менеджеры × статусы поквартально (patch v30): нагрузка менеджеров по плановым
 * кварталам — сумма и число сделок в каждой стадии.
 *
 * Данные — DashboardDataService::sales() без фильтра страницы воронки (стадии
 * scopeStatuses, пересчёт в валюту виджета), плановый квартал — поле сделки
 * DealProjectService::ufQuarter(). Колонки-кварталы общие с CountryQuarterWidget.
 *
 * Клик по менеджеру — реестр сделок с его отбором; сделки без планового квартала
 * в столбцы не попадают и показываются предупреждением под таблицей.
 */
class ManagerQuarterWidget extends Widget
{
    public static function id(): string { return 'manager_quarter'; }

    public static function name(): string { return 'Менеджеры × статусы поквартально'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Нагрузка менеджеров по плановым кварталам: суммы и число сделок по стадиям';
    }

    public static function icon(): string { return 'fa-user-group'; }

    public static function sizes(): array { return ['16x8', '32x8']; }

    public static function defaultSize(): string { return '16x8'; }

    public static function order(): int { return 240; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'quarters', 'type' => 'select', 'label' => 'Кварталов', 'default' => '4', 'options' => ['4' => '4', '8' => '8'],
                'hint' => 'От текущего квартала вперёд'],
            ['key' => 'rows', 'type' => 'select', 'label' => 'Строки', 'default' => 'manager_status',
                'options' => ['manager_status' => 'Менеджер → статус', 'manager' => 'Только менеджеры']],
            ['key' => 'managers', 'type' => 'list', 'label' => 'Менеджеры', 'default' => [],
                'options' => fn() => static::managerOptions(), 'hint' => 'Пусто — все менеджеры'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('crm-deal.index', ['has_proposal' => 'all']);
    }

    /**
     * Менеджеры сделок для настройки: «[83] Анна Август.» → чистое имя
     *
     * @return array значение фильтра => подпись
     */
    public static function managerOptions(): array
    {
        return ProposalDealService::managers()
            ->mapWithKeys(fn($item) => [(string) $item => static::managerName((string) $item)])
            ->all();
    }

    /**
     * Имя менеджера без кода Битрикса: «[83] Анна Август.» → «Анна Август.»
     *
     * @param string $assigned_by
     * @return string
     */
    public static function managerName(string $assigned_by): string
    {
        return trim(Str::afterLast($assigned_by, ']')) ?: $assigned_by;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $columns = CountryQuarterWidget::columns((int) $settings['quarters']);
        $keys = array_column($columns, 'key');
        $matrix = [];

        // 8 менеджеров по 2–4 статуса (~24 строки): высокому блоку есть чем заполниться
        $managers = ['Александр Суханов', 'Алексей Карасев', 'Анна Август.', 'Дмитрий Орлов',
            'Екатерина Белова', 'Игорь Волков', 'Мария Кузнецова', 'Пётр Смирнов'];
        $statuses = ['Lead', 'Research', 'Presentation', 'Pilot project', 'TCP', 'Contracting'];

        foreach ($managers as $m => $manager) {
            foreach (array_slice($statuses, $m % 3, 2 + ($m * 5) % 3) as $s => $status) {
                foreach ($keys as $q => $key) {
                    $k = ($m + 2) * ($s + 3) + $q * 5;
                    if ($k % 4 === 0) continue;

                    $matrix[$manager]['name'] = $manager;
                    $matrix[$manager]['cells'][$status][$key] = ['amount' => ($k % 9 + 1) * 3150000.0, 'count' => $k % 4 + 1];
                }
            }
        }

        return static::table($matrix, $columns, $settings['rows'], '₽', 5, 21700000.0);
    }

    /**
     * Матрица сумм: менеджер → статус → плановый квартал
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['columns', 'rows', 'col_totals', 'grand_total', 'count_total', 'max_cell', 'symbol', 'none_count', 'none_amount']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $columns = CountryQuarterWidget::columns((int) $settings['quarters']);
        $keys = array_column($columns, 'key');
        $field = DealProjectService::ufQuarter();
        $only = array_map('strval', (array) $settings['managers']);

        $result = (new DashboardDataService($currency, false))->sales();

        $matrix = [];
        $none_count = 0;
        $none_amount = 0.0;

        foreach ($result['deals'] as $deal) {
            // как на странице воронки: сделки без суммы в матрицу не идут
            $amount = (float) $deal->opportunity_RUB;
            if ($amount == 0.0) continue;

            $assigned_by = (string) $deal->assigned_by;
            if ($only && !in_array($assigned_by, $only, true)) continue;

            $quarter = trim((string) ($deal->{$field} ?? ''));

            // квартал вне выбранного окна («не выбрано», прошлые и слишком далёкие) — в предупреждение
            if (!in_array($quarter, $keys, true)) {
                $none_count++;
                $none_amount += $amount;
                continue;
            }

            $name = static::managerName($assigned_by);
            $status = (string) $deal->stage_name;

            $matrix[$name]['name'] = $name;
            $matrix[$name]['url'] = route('crm-deal.index', ['manager' => [$assigned_by], 'has_proposal' => 'all']);
            $matrix[$name]['cells'][$status][$quarter]['amount'] = ($matrix[$name]['cells'][$status][$quarter]['amount'] ?? 0.0) + $amount;
            $matrix[$name]['cells'][$status][$quarter]['count'] = ($matrix[$name]['cells'][$status][$quarter]['count'] ?? 0) + 1;
        }

        return static::table($matrix, $columns, $settings['rows'], $ctx->symbol($currency), $none_count, $none_amount);
    }

    /**
     * Строки, итоги и максимум ячейки
     *
     * @param array $matrix менеджер → ['name', 'url', 'cells' => статус → квартал → ['amount', 'count']]
     * @param array $columns
     * @param string $mode manager_status | manager
     * @param string $symbol
     * @param int $none_count сделки без планового квартала
     * @param float $none_amount
     * @return array
     */
    protected static function table(array $matrix, array $columns, string $mode, string $symbol, int $none_count, float $none_amount): array
    {
        $keys = array_column($columns, 'key');
        $order = array_flip(FunnelTableWidget::STAGES);
        ksort($matrix);

        $rows = [];
        foreach ($matrix as $manager) {
            $statuses = $manager['cells'] ?? [];
            uksort($statuses, fn($a, $b) => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);
            $lines = $statuses;

            // «Только менеджеры»: суммы по всем стадиям менеджера
            if ($mode === 'manager') {
                $sum = [];
                foreach ($statuses as $cells) {
                    foreach ($cells as $key => $cell) {
                        $sum[$key]['amount'] = ($sum[$key]['amount'] ?? 0.0) + (float) $cell['amount'];
                        $sum[$key]['count'] = ($sum[$key]['count'] ?? 0) + (int) $cell['count'];
                    }
                }
                $lines = ['' => $sum];
            }

            $first = true;
            foreach ($lines as $status => $cells) {
                $row = [
                    'manager' => (string) $manager['name'],
                    'url' => (string) ($manager['url'] ?? ''),
                    'status' => (string) $status,
                    'first' => $first,
                    'cells' => [],
                    'total' => 0.0,
                    'count' => 0,
                ];

                foreach ($keys as $key) {
                    $cell = ['amount' => (float) ($cells[$key]['amount'] ?? 0.0), 'count' => (int) ($cells[$key]['count'] ?? 0)];
                    $row['cells'][$key] = $cell;
                    $row['total'] += $cell['amount'];
                    $row['count'] += $cell['count'];
                }

                $rows[] = $row;
                $first = false;
            }
        }

        $col_totals = [];
        foreach ($keys as $key) {
            $col_totals[$key] = [
                'amount' => array_sum(array_map(fn($row) => $row['cells'][$key]['amount'], $rows)),
                'count' => array_sum(array_map(fn($row) => $row['cells'][$key]['count'], $rows)),
            ];
        }

        $amounts = [];
        foreach ($rows as $row) {
            foreach ($row['cells'] as $cell) {
                $amounts[] = abs($cell['amount']);
            }
        }

        return [
            'columns' => $columns,
            'rows' => $rows,
            'col_totals' => $col_totals,
            'grand_total' => array_sum(array_column($col_totals, 'amount')),
            'count_total' => array_sum(array_column($col_totals, 'count')),
            'max_cell' => $amounts ? max($amounts) : 0.0,
            'symbol' => $symbol,
            'none_count' => $none_count,
            'none_amount' => $none_amount,
        ];
    }
}
