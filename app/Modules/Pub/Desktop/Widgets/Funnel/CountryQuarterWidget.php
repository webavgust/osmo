<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmDeal\Repositories\CrmDealRepository;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Страны × статусы поквартально (patch v30): суммы сделок по плановому кварталу —
 * тот же разрез, что у помесячной матрицы (CountryMonthWidget), но шагом в квартал.
 *
 * Данные — DashboardDataService::sales() без фильтра страницы воронки (стадии
 * scopeStatuses, пересчёт в валюту виджета); плановый квартал лежит в поле сделки
 * DealProjectService::ufQuarter() («2026q3»), страна — поле компании, поэтому она
 * подтягивается одной картой company_id → страна, без запроса на каждую сделку.
 *
 * Квартал «не выбрано» в столбцы не попадает: такие сделки считаются отдельно и
 * показываются предупреждением под таблицей.
 */
class CountryQuarterWidget extends Widget
{
    /** Римские номера кварталов */
    public const QUARTERS = [1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV'];

    public static function id(): string { return 'country_quarter'; }

    public static function name(): string { return 'Страны × статусы поквартально'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Суммы сделок по странам и статусам на ближайшие кварталы';
    }

    public static function icon(): string { return 'fa-table-cells-large'; }

    public static function sizes(): array { return ['16x8', '32x8']; }

    public static function defaultSize(): string { return '16x8'; }

    public static function order(): int { return 220; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'quarters', 'type' => 'select', 'label' => 'Кварталов', 'default' => '4', 'options' => ['4' => '4', '8' => '8'],
                'hint' => 'От текущего квартала вперёд'],
            ['key' => 'rows', 'type' => 'select', 'label' => 'Строки', 'default' => 'country_status',
                'options' => ['country_status' => 'Страна → статус', 'country' => 'Только страны']],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('dashboard.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $columns = static::columns((int) $settings['quarters']);
        $keys = array_column($columns, 'key');
        $matrix = [];

        // до ~40 строк: высоким блокам должно быть чем заполниться
        $countries = ['Азербайджан', 'Армения', 'Беларусь', 'Грузия', 'Казахстан', 'Киргизия', 'Россия', 'Узбекистан'];
        foreach ($countries as $c => $country) {
            foreach (['Presentation', 'TCP', 'Contracting', 'Invoice + Specification', 'Execution (PRE-PAYMENT)'] as $s => $status) {
                foreach ($keys as $q => $key) {
                    $k = ($c + 2) * ($s + 3) + $q * 5;
                    if ($k % 4 !== 0) $matrix[$country][$status][$key] = ($k % 9 + 1) * 2350000.0;
                }
            }
        }

        return static::table($matrix, $columns, $settings['rows'], '₽', 6, 18400000.0);
    }

    /**
     * Матрица сумм: страна → статус → плановый квартал
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['columns', 'rows', 'col_totals', 'grand_total', 'max_cell', 'symbol', 'none_count', 'none_amount']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $columns = static::columns((int) $settings['quarters']);
        $keys = array_column($columns, 'key');
        $field = DealProjectService::ufQuarter();

        $result = (new DashboardDataService($currency, false))->sales();
        $countries = static::countries();

        $matrix = [];
        $none_count = 0;
        $none_amount = 0.0;

        foreach ($result['deals'] as $deal) {
            // как на странице воронки: сделки без суммы в матрицу не идут
            $amount = (float) $deal->opportunity_RUB;
            if ($amount == 0.0) continue;

            $quarter = trim((string) ($deal->{$field} ?? ''));

            // квартал вне выбранного окна («не выбрано», прошлые и слишком далёкие) — в предупреждение
            if (!in_array($quarter, $keys, true)) {
                $none_count++;
                $none_amount += $amount;
                continue;
            }

            $country = $countries[(int) $deal->company_id] ?? CrmDealRegistryService::COUNTRY_EMPTY;
            $status = (string) $deal->stage_name;

            $matrix[$country][$status][$quarter] = ($matrix[$country][$status][$quarter] ?? 0.0) + $amount;
        }

        return static::table($matrix, $columns, $settings['rows'], $ctx->symbol($currency), $none_count, $none_amount);
    }

    /**
     * Страны компаний Битрикса одной картой: company_id → страна
     *
     * @return array
     */
    protected static function countries(): array
    {
        $field = CrmDealRepository::ufCountry();

        return DB::connection('bitrix')->table('crm_company_uf')
            ->pluck($field, 'company_id')
            ->map(fn($value) => trim((string) $value) !== '' ? trim((string) $value) : CrmDealRegistryService::COUNTRY_EMPTY)
            ->all();
    }

    /**
     * Колонки-кварталы от текущего: ['key' => '2026q3', 'label' => "III'26", 'title' => 'III квартал 2026']
     *
     * @param int $count сколько кварталов
     * @return array
     */
    public static function columns(int $count): array
    {
        $start = Carbon::now()->startOfQuarter();

        return array_map(function ($i) use ($start) {
            $date = $start->copy()->addQuarters($i);
            $number = (int) $date->quarter;

            return [
                'key' => $date->year . 'q' . $number,
                'label' => static::QUARTERS[$number] . "'" . $date->format('y'),
                'title' => static::QUARTERS[$number] . ' квартал ' . $date->year,
            ];
        }, range(0, max(1, $count) - 1));
    }

    /**
     * Строки, итоги и максимум ячейки
     *
     * @param array $matrix страна → статус → квартал ('2026q3') → сумма
     * @param array $columns
     * @param string $mode country_status | country
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
        foreach ($matrix as $country => $statuses) {
            uksort($statuses, fn($a, $b) => [$order[$a] ?? PHP_INT_MAX, $a] <=> [$order[$b] ?? PHP_INT_MAX, $b]);
            $lines = $statuses;

            // «Только страны»: суммы по всем статусам страны
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
                $row = ['country' => (string) $country, 'status' => (string) $status, 'first' => $first, 'cells' => [], 'total' => 0.0];
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
            'none_count' => $none_count,
            'none_amount' => $none_amount,
        ];
    }
}
