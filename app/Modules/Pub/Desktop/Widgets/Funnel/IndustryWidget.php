<?php

namespace App\Modules\Pub\Desktop\Widgets\Funnel;

use App\Modules\Bitrix\CrmCompany\Models\CrmCompany;
use App\Modules\Bitrix\CrmDeal\Services\CrmDealRegistryService;
use App\Modules\Bitrix\Dashboard\Services\DashboardDataService;
use App\Modules\Pub\DealProject\Services\DealProjectService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * Отрасли (patch v30): сделки воронки в разрезе сферы деятельности заказчика —
 * сумма, количество и ведущий менеджер отрасли.
 *
 * Данные — DashboardDataService::sales() (стадии воронки, пересчёт в валюту виджета)
 * без фильтра страницы воронки; отрасль берётся у конечного заказчика сделки
 * (поле uf_crm_1717755645 сопоставляется с названием компании в crm_company) —
 * так же, как это делает таблица «Отрасли» на странице воронки. Если заказчик не
 * сопоставился, в дело идёт компания сделки, а совсем без отрасли — «Неизвестно».
 *
 * Отрасли не считаются по одной запросом на каждую (как industry_name()): карта
 * «компания → отрасль» собирается одним запросом, дальше всё в памяти.
 */
class IndustryWidget extends Widget
{
    /** Отбор по плановому кварталу сделки */
    public const QUARTERS = [
        'all' => 'Все сделки',
        'current' => 'Текущий плановый квартал',
        'next' => 'Следующий плановый квартал',
        'year' => 'Плановые кварталы года',
    ];

    /** Подпись строки, в которую сворачивается хвост списка */
    public const REST = 'Остальные';

    /** Отрасль не заполнена или заказчик не сопоставлен */
    public const UNKNOWN = 'Неизвестно';

    public static function id(): string { return 'industry'; }

    public static function name(): string { return 'Отрасли'; }

    public static function category(): string { return 'funnel'; }

    public static function description(): string
    {
        return 'Сделки по сферам деятельности заказчиков: сумма и количество';
    }

    public static function icon(): string { return 'fa-industry-windows'; }

    public static function sizes(): array { return ['16x8', '8x8', '8x4']; }

    public static function defaultSize(): string { return '16x8'; }

    public static function order(): int { return 300; }

    public static function ttl(): int { return 600; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько отраслей', 'default' => 10, 'min' => 3, 'max' => 30,
                'hint' => 'Остальные сворачиваются в одну строку'],
            ['key' => 'quarter', 'type' => 'select', 'label' => 'Квартал', 'default' => 'all', 'options' => static::QUARTERS],
            ['key' => 'managers', 'type' => 'bool', 'label' => 'Показывать ведущего менеджера', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('dashboard.index');
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // 14 отраслей, хвост сворачивается по настройке «Сколько отраслей» — как в data()
        $rows = [
            ['Добыча/Нефть/Газ', 34, 218.4e6, 'Анна Август.'],
            ['Производство', 28, 164.9e6, 'Пётр Смирнов'],
            ['ИТ/Софт/Телеком', 22, 121.3e6, 'Анна Август.'],
            ['Энергетика', 14, 72.6e6, 'Игорь Волков'],
            ['Логистика/Транспорт', 11, 58.7e6, 'Игорь Волков'],
            ['Металлургия', 9, 47.1e6, 'Пётр Смирнов'],
            ['Гос. услуги', 8, 41.2e6, 'Пётр Смирнов'],
            ['Строительство', 7, 33.8e6, 'Анна Август.'],
            ['Сельcкое хозяйство', 6, 19.5e6, 'Игорь Волков'],
            ['Химия', 4, 14.2e6, 'Пётр Смирнов'],
            ['Ритейл', 4, 11.6e6, 'Анна Август.'],
            [static::UNKNOWN, 5, 7.8e6, null],
            ['Финансы', 2, 5.1e6, 'Игорь Волков'],
            ['Медицина', 1, 2.4e6, 'Анна Август.'],
        ];

        $rows = array_map(fn($row) => [
            'industry' => $row[0],
            'count' => $row[1],
            'amount' => $row[2],
            'manager' => $row[3],
        ], $rows);

        return static::shape(static::fold($rows, max(1, (int) $settings['limit'])), '₽',
            static::QUARTERS[$settings['quarter']] ?? static::QUARTERS['all']);
    }

    /**
     * Сделки воронки по отраслям заказчиков
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['industry', 'count', 'amount', 'manager', 'share', 'bar']], 'total', 'count_total', 'symbol', 'label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $currency = $ctx->currencyFor($settings);
        $limit = max(1, (int) $settings['limit']);
        $quarters = static::quarterKeys($settings['quarter']);

        $customer_field = CrmDealRegistryService::ufCustomer();
        $quarter_field = DealProjectService::ufQuarter();

        // карта «компания → отрасль» одним запросом: и по названию (конечный заказчик), и по id
        $by_title = [];
        $by_id = [];
        foreach (CrmCompany::query()->get(['id', 'title', 'industry_name']) as $company) {
            $industry = trim((string) $company->industry_name);
            $title = trim((string) $company->title);

            if ($title !== '' && !array_key_exists($title, $by_title)) $by_title[$title] = $industry;
            $by_id[(int) $company->id] = $industry;
        }

        $result = (new DashboardDataService($currency, false))->sales();

        $groups = [];
        foreach ($result['deals'] as $deal) {
            $amount = (float) $deal->opportunity_RUB;

            // страница воронки считает отрасли только по сделкам с суммой
            if ($amount <= 0) continue;

            if ($quarters !== null && !in_array(trim((string) ($deal->{$quarter_field} ?? '')), $quarters, true)) continue;

            $customer = trim((string) ($deal->{$customer_field} ?? ''));
            $industry = $by_title[$customer] ?? $by_id[(int) $deal->company_id] ?? '';
            $industry = $industry !== '' ? $industry : static::UNKNOWN;

            $groups[$industry] ??= ['industry' => $industry, 'count' => 0, 'amount' => 0.0, 'managers' => []];
            $groups[$industry]['count']++;
            $groups[$industry]['amount'] += $amount;

            $manager = trim(Str::afterLast((string) $deal->assigned_by, ']'));
            if ($manager !== '') {
                $groups[$industry]['managers'][$manager] = ($groups[$industry]['managers'][$manager] ?? 0.0) + $amount;
            }
        }

        // ведущий менеджер отрасли — тот, у кого в ней больше сумма
        $rows = array_map(function ($group) {
            arsort($group['managers']);
            $group['manager'] = array_key_first($group['managers']);
            unset($group['managers']);

            return $group;
        }, array_values($groups));

        usort($rows, fn($a, $b) => [$b['amount'], $b['count']] <=> [$a['amount'], $a['count']]);

        return static::shape(static::fold($rows, $limit), $ctx->symbol($currency), static::QUARTERS[$settings['quarter']] ?? static::QUARTERS['all']);
    }

    /**
     * Кварталы отбора: null — без отбора
     *
     * @param string $mode ключ QUARTERS
     * @return array|null ['2026q3', …]
     */
    protected static function quarterKeys(string $mode): ?array
    {
        $now = now();

        return match ($mode) {
            'current' => [$now->year . 'q' . $now->quarter],
            'next' => [$now->copy()->addQuarterNoOverflow()->year . 'q' . $now->copy()->addQuarterNoOverflow()->quarter],
            'year' => array_map(fn($q) => $now->year . 'q' . $q, [1, 2, 3, 4]),
            default => null,
        };
    }

    /**
     * Хвост списка — одной строкой «Остальные»
     *
     * @param array $rows
     * @param int $limit
     * @return array
     */
    protected static function fold(array $rows, int $limit): array
    {
        if (count($rows) <= $limit) return $rows;

        $head = array_slice($rows, 0, $limit - 1);
        $tail = array_slice($rows, $limit - 1);

        $head[] = [
            'industry' => static::REST,
            'count' => array_sum(array_column($tail, 'count')),
            'amount' => array_sum(array_column($tail, 'amount')),
            'manager' => null,
            'rest' => count($tail),
        ];

        return $head;
    }

    /**
     * Доли и длина полос
     *
     * @param array $rows [['industry', 'count', 'amount', 'manager']]
     * @param string $symbol
     * @param string $label
     * @return array
     */
    protected static function shape(array $rows, string $symbol, string $label): array
    {
        $total = array_sum(array_column($rows, 'amount'));
        $max = $rows ? max(array_map('abs', array_column($rows, 'amount'))) : 0.0;

        foreach ($rows as &$row) {
            $row['manager'] ??= null;
            $row['rest'] ??= 0;
            $row['share'] = $total != 0.0 ? round($row['amount'] / $total * 100, 1) : 0.0;
            $row['bar'] = $max > 0 ? round(abs($row['amount']) / $max * 100, 1) : 0.0;
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
