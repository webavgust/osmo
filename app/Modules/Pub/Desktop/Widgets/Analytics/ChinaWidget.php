<?php

namespace App\Modules\Pub\Desktop\Widgets\Analytics;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\Report\Services\ChinaReportService;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Китай: сводка (patch v30) — ключевые цифры отчёта «Китай» (/report/china).
 *
 * Отчёт собирает ChinaReportService::getData__China1(): действующие лицензионные
 * ключи (active_to позже сегодня), у каждого — компания, страна, партнёр, количество
 * лицензий и сумма КП договора (минимальный вариант последнего КП, в валюте этого КП).
 * Виджет считает по той же таблице: сколько ключей, компаний, стран, лицензий,
 * на какую сумму и сколько ключей истекает в ближайшие три месяца — отчёт эти ключи
 * подсвечивает.
 *
 * Сумму КП отчёт повторяет в строке каждого ключа договора, поэтому в итог она идёт
 * один раз на договор; в разрезе — у строки, где ключей этого договора больше.
 * Суммы КП сведены в валюту виджета по текущему курсу; договоры, для валюты которых курса
 * нет, в сумму не попадают и считаются в skipped. Года у отчёта нет — он всегда «на сегодня».
 *
 * Сам файл отчёта на странице собирается формой (выбор ключей, валюты и курсов вручную),
 * поэтому кнопка виджета ведёт на страницу отчёта, а не выгружает файл.
 */
class ChinaWidget extends Widget
{
    /** Разрезы списка: код => [подпись, номер колонки отчёта] */
    public const SCOPES = [
        'company' => ['Компании', 0],
        'country' => ['Страны', 1],
        'partner' => ['Партнёры', 2],
    ];

    public static function id(): string { return 'china'; }

    public static function name(): string { return 'Китай: сводка'; }

    public static function category(): string { return 'analytics'; }

    public static function description(): string
    {
        return 'Отчёт «Китай» в цифрах: действующие ключи, лицензии, суммы';
    }

    public static function icon(): string { return 'fa-earth-asia'; }

    public static function sizes(): array { return ['8x4', '4x2', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 130; }

    public static function usesCurrency(): bool { return true; }

    public static function ttl(): int { return 1800; }

    public static function available(User $user): bool
    {
        // отчёт открыт всем, у кого есть доступ к порталу (пункт меню — general_access)
        return (bool) $user->can_do('general_access');
    }

    public static function fields(): array
    {
        return [
            ['key' => 'scope', 'type' => 'select', 'label' => 'Разрез списка', 'default' => 'company',
                'options' => collect(static::SCOPES)->map(fn($scope) => $scope[0])->all(),
                'hint' => 'Виден в высоком блоке'],
            ['key' => 'button', 'type' => 'bool', 'label' => 'Кнопка «Открыть отчёт»', 'default' => true,
                'hint' => 'Файл отчёта собирается на самой странице: там выбирают ключи, валюту и курсы'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // у отчёта «Китай» нет именованного маршрута
        return url('/report/china');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 40 строк: высокому блоку должно быть чем заполниться
        $rows = [
            ['name' => 'BMW (Китай)', 'keys' => 4, 'licenses' => 40, 'amount' => 45114240.0],
            ['name' => 'Amna Company Limited', 'keys' => 2, 'licenses' => 12, 'amount' => 8300000.0],
            ['name' => 'Shenzhen Vision', 'keys' => 1, 'licenses' => 6, 'amount' => 4200000.0],
        ];
        $cities = ['Guangzhou', 'Hangzhou', 'Chengdu', 'Wuhan', 'Nanjing', 'Suzhou', 'Tianjin', 'Qingdao', 'Xiamen', 'Dalian'];
        $kinds = ['Smart City', 'Security Technology', 'Logistics', 'Retail Group'];

        for ($i = 0; count($rows) < 40; $i++) {
            $keys = max(1, 3 - intdiv($i, 12));
            $rows[] = [
                'name' => $cities[$i % 10] . ' ' . $kinds[intdiv($i, 10) % 4],
                'keys' => $keys, 'licenses' => $keys * (8 - $i % 5),
                'amount' => round(3900000 / (1 + $i * 0.18), -3),
            ];
        }

        return [
            'keys' => array_sum(array_column($rows, 'keys')), 'companies' => count($rows), 'countries' => 11, 'partners' => 9,
            'licenses' => array_sum(array_column($rows, 'licenses')), 'amount' => array_sum(array_column($rows, 'amount')),
            'expiring' => 6, 'skipped' => 2,
            'symbol' => '₽', 'scope_label' => 'Компании', 'url' => url('/report/china'),
            'rows' => $rows,
        ];
    }

    /**
     * Ключевые цифры отчёта «Китай»
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['keys', 'companies', 'countries', 'partners', 'licenses', 'amount',
     *     'expiring', 'skipped', 'symbol', 'scope_label', 'url',
     *     'rows' => [['name', 'keys', 'licenses', 'amount']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $scope = isset(static::SCOPES[$settings['scope']]) ? (string) $settings['scope'] : 'company';
        [$scope_label, $column] = static::SCOPES[$scope];

        $out = [
            'keys' => 0, 'companies' => 0, 'countries' => 0, 'partners' => 0,
            'licenses' => 0, 'amount' => 0.0, 'expiring' => 0, 'skipped' => 0,
            'symbol' => $ctx->symbol($currency), 'scope_label' => $scope_label,
            'url' => static::sourceUrl($settings), 'rows' => [],
        ];

        // отчёт падает на пустой выборке, поэтому сначала убеждаемся, что ключи есть
        if (!LicenseKey::where('active_to', '>', now())->exists()) {
            return $out;
        }

        $grid = ChinaReportService::getData__China1();
        $contract_of = static::keyContracts($grid);
        $companies = $countries = $partners = $groups = $contracts = [];

        foreach ($grid as $row) {
            // строки одного ключа склеены: считаем только первую, остальные — его же комментарий
            if (empty($row[0])) continue;

            $out['keys']++;
            $companies[(string) $row[0]['cell']] = true;
            $countries[(string) ($row[1]['cell'] ?? '')] = true;
            $partners[(string) ($row[2]['cell'] ?? '')] = true;

            $licenses = (int) ($row[7]['cell'] ?? 0);
            $out['licenses'] += $licenses;

            if (!empty($row[5]['class']['warning'])) $out['expiring']++;

            $name = trim((string) ($row[$column]['cell'] ?? ''));
            if ($name === '') $name = '—';

            $groups[$name] ??= ['name' => $name, 'keys' => 0, 'licenses' => 0, 'amount' => 0.0];
            $groups[$name]['keys']++;
            $groups[$name]['licenses'] += $licenses;

            // отчёт повторяет сумму КП договора в строке каждого его ключа — в итог она идёт один раз
            $sum = (float) ($row[3]['cell'] ?? 0);
            if ($sum <= 0) continue;

            $key = (int) $row[0]['system'];
            $contract = $contract_of[$key] ?? 'key' . $key;
            $contracts[$contract] ??= ['sum' => $sum, 'currency' => $row[3]['currency'] ?? null, 'groups' => []];
            $contracts[$contract]['groups'][$name] = ($contracts[$contract]['groups'][$name] ?? 0) + 1;
        }

        foreach ($contracts as $contract) {
            // сумма КП идёт в валюте самого КП — сводим к валюте виджета по текущему курсу
            $converted = CurrencyService::convertAmount($contract['sum'], $contract['currency'], $currency, now());
            if ($converted === null) {
                $out['skipped']++;
                continue;
            }

            // ключи договора попали в разные строки разреза — сумма у строки, где их больше
            arsort($contract['groups']);
            $groups[array_key_first($contract['groups'])]['amount'] += (float) $converted;
            $out['amount'] += (float) $converted;
        }

        $out['companies'] = count($companies);
        $out['countries'] = count($countries);
        $out['partners'] = count($partners);
        $out['amount'] = round($out['amount'], 2);

        $rows = array_values($groups);
        usort($rows, fn($a, $b) => [$b['amount'], $b['keys'], $a['name']] <=> [$a['amount'], $a['keys'], $b['name']]);

        foreach ($rows as $i => $row) {
            $rows[$i]['amount'] = round($row['amount'], 2);
        }

        $out['rows'] = $rows;

        return $out;
    }

    /**
     * Договор каждого ключа отчёта: ключ → спецификация → договор
     *
     * @param array $grid строки ChinaReportService::getData__China1()
     * @return array id ключа => id договора (ключи без спецификации не попадают)
     */
    protected static function keyContracts(array $grid): array
    {
        $keys = [];
        foreach ($grid as $row) {
            if (!empty($row[0]['system'])) $keys[] = (int) $row[0]['system'];
        }

        if (!$keys) return [];

        return DB::table('license_keys as k')
            ->join('contract_specifications as cs', 'cs.id', '=', 'k.contract_specification_id')
            ->whereIn('k.id', $keys)
            ->pluck('cs.contract_id', 'k.id')
            ->all();
    }
}
