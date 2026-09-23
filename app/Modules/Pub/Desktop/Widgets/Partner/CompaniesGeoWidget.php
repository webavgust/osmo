<?php

namespace App\Modules\Pub\Desktop\Widgets\Partner;

use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Компании по странам и секторам (patch v30): распределение компаний портала
 * по выбранному разрезу — страна, сектор или партнёр.
 *
 * Показатель — количество компаний либо сумма их спецификаций. Суммы
 * пересчитываются в валюту виджета по курсу на дату спецификации (как это
 * делают остальные денежные виджеты); спецификации без курса не суммируются,
 * а считаются в skipped.
 */
class CompaniesGeoWidget extends Widget
{
    /** Разрезы: ключ => [подпись колонки, поле-источник, подпись пустого значения] */
    public const DIMENSIONS = [
        'country' => ['Страна', 'Страна не указана'],
        'sector' => ['Сектор', 'Без сектора'],
        'partner' => ['Партнёр', 'Без партнёра'],
    ];

    public static function id(): string { return 'companies_geo'; }

    public static function name(): string { return 'Компании по странам'; }

    public static function category(): string { return 'partner'; }

    public static function description(): string
    {
        return 'Распределение компаний по странам, секторам или партнёрам: количество и доли';
    }

    public static function icon(): string { return 'fa-earth-europe'; }

    public static function sizes(): array { return ['8x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 150; }

    public static function ttl(): int { return 900; }

    public static function usesCurrency(): bool { return true; }

    public static function fields(): array
    {
        return [
            ['key' => 'by', 'type' => 'select', 'label' => 'Разрез', 'default' => 'country',
                'options' => ['country' => 'Страна', 'sector' => 'Сектор', 'partner' => 'Партнёр']],
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель', 'default' => 'count',
                'options' => ['count' => 'Количество компаний', 'amount' => 'Сумма спецификаций'],
                'hint' => 'Сумма считается по спецификациям компаний разреза, без отменённых'],
            ['key' => 'only_active', 'type' => 'bool', 'label' => 'Только активные компании', 'default' => false],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько строк', 'default' => 30, 'min' => 3, 'max' => 30,
                'hint' => 'Сколько строк влезет в блок — решает высота; остальное попадает в «ещё»'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // отбор списка компаний живёт в кэше фильтра, а не в адресе — ведём на сам список
        return route('company.index');
    }

    /**
     * Образцовые данные: 30 стран, чтобы было чем заполнить высокий блок; отбор по «Сколько строк» —
     * как у живых данных
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $sample = ['Россия' => 138, 'Индия' => 24, 'Саудовская Аравия' => 17, 'Испания' => 14, 'Китай' => 12,
            'Катар' => 10, 'ОАЭ' => 9, 'Казахстан' => 8, 'Узбекистан' => 7, 'Беларусь' => 6, 'Турция' => 6,
            'Египет' => 5, 'Вьетнам' => 5, 'Индонезия' => 4, 'Малайзия' => 4, 'Бразилия' => 3, 'Мексика' => 3,
            'Армения' => 3, 'Азербайджан' => 2, 'Киргизия' => 2, 'Таджикистан' => 2, 'Монголия' => 2,
            'Иран' => 1, 'Ирак' => 1, 'Алжир' => 1, 'Сербия' => 1, 'Италия' => 1, 'Германия' => 1,
            'Франция' => 1, 'ЮАР' => 1];

        $rows = [];
        $i = 0;
        foreach ($sample as $name => $count) {
            $amount = $count * 610000.0 * (1 + ($i++ % 3) / 10);
            $rows[] = ['name' => $name, 'count' => $count, 'value' => $settings['metric'] === 'amount' ? $amount : (float) $count];
        }
        usort($rows, fn($a, $b) => [$b['value'], $b['count'], $a['name']] <=> [$a['value'], $a['count'], $b['name']]);

        $limit = (int) $settings['limit'];
        $tail = array_slice($rows, $limit);

        return static::result(
            array_slice($rows, 0, $limit),
            [
                'rows' => count($tail),
                'count' => (int) array_sum(array_column($tail, 'count')),
                'value' => (float) array_sum(array_column($tail, 'value')),
            ],
            $settings,
            $ctx->symbol($ctx->currencyFor($settings)),
            0
        );
    }

    /**
     * Распределение компаний по разрезу
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['by', 'dim_label', 'metric', 'metric_label', 'money', 'symbol', 'total',
     *     'total_count', 'rest', 'rest_count', 'rest_value', 'skipped',
     *     'rows' => [['name', 'count', 'value', 'share']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $by = (string) $settings['by'];
        $money = (string) $settings['metric'] === 'amount';
        $currency = $ctx->currencyFor($settings);
        $only_active = !empty($settings['only_active']);
        $empty_label = static::DIMENSIONS[$by][1] ?? 'Не указано';

        // компании разреза: количество и, для денег, список id
        $companies = static::companies($by, $only_active);

        $rows = [];
        foreach ($companies as $row) {
            $name = trim((string) ($row->label ?? '')) !== '' ? (string) $row->label : $empty_label;
            $rows[$name]['name'] = $name;
            $rows[$name]['count'] = ($rows[$name]['count'] ?? 0) + 1;
            $rows[$name]['value'] = $rows[$name]['count'];
            $rows[$name]['ids'][] = (int) $row->id;
        }

        $skipped = 0;

        if ($money) {
            $amounts = static::amounts($currency, $skipped);

            foreach ($rows as $name => $row) {
                $rows[$name]['value'] = array_sum(array_map(fn($id) => $amounts[$id] ?? 0.0, $row['ids']));
            }
        }

        // id компаний в кэш не кладём — они нужны были только для сумм
        $rows = array_map(fn($row) => ['name' => $row['name'], 'count' => $row['count'], 'value' => (float) $row['value']], $rows);
        usort($rows, fn($a, $b) => [$b['value'], $b['count'], $a['name']] <=> [$a['value'], $a['count'], $b['name']]);

        $limit = (int) $settings['limit'];
        $tail = array_slice($rows, $limit);

        return static::result(
            array_slice($rows, 0, $limit),
            [
                'rows' => count($tail),
                'count' => (int) array_sum(array_column($tail, 'count')),
                'value' => (float) array_sum(array_column($tail, 'value')),
            ],
            $settings,
            $ctx->symbol($currency),
            $skipped
        );
    }

    /**
     * Компании с подписью разреза: id + label
     *
     * @param string $by country | sector | partner
     * @param bool $only_active
     * @return \Illuminate\Support\Collection
     */
    protected static function companies(string $by, bool $only_active)
    {
        $query = DB::table('companies as c')
            ->when($only_active, fn($builder) => $builder->where('c.active', 1));

        match ($by) {
            'sector' => $query->leftJoin('sectors as d', 'd.id', '=', 'c.sector_id'),
            'partner' => $query->leftJoin('partners as d', 'd.id', '=', 'c.partner_id'),
            default => $query->leftJoin('countries as d', 'd.id', '=', 'c.country_id'),
        };

        return $query->get(['c.id', 'd.name as label']);
    }

    /**
     * Сумма спецификаций каждой компании в валюте виджета: company_id => сумма.
     * Отменённые спецификации не суммируются (как в суммах договора); спецификация без своей
     * компании относится к компании договора — как в карточке компании и платёжном календаре.
     * Курс — на дату спецификации; спецификации без курса считаются в $skipped
     *
     * @param string $currency
     * @param int $skipped
     * @return array
     */
    protected static function amounts(string $currency, int &$skipped): array
    {
        $out = [];

        $specs = DB::table('contract_specifications as s')
            ->leftJoin('contracts as c', 'c.id', '=', 's.contract_id')
            ->where(fn($query) => $query->whereNull('s.status')->orWhere('s.status', '!=', 'canceled'))
            ->whereRaw('COALESCE(s.company_id, c.company_id) IS NOT NULL')
            ->get([DB::raw('COALESCE(s.company_id, c.company_id) as company_id'), 's.amount', 's.currency_slug', 's.date_create']);

        foreach ($specs as $spec) {
            $date = $spec->date_create ? Carbon::parse($spec->date_create) : now();
            $amount = CurrencyService::convertAmount((float) $spec->amount, $spec->currency_slug, $currency, $date);

            if ($amount === null) {
                $skipped++;
                continue;
            }

            $out[(int) $spec->company_id] = ($out[(int) $spec->company_id] ?? 0.0) + $amount;
        }

        return $out;
    }

    /**
     * Доли, итоги и подписи — общий вид данных для живых и образцовых строк
     *
     * @param array $rows
     * @param array $rest хвост, не поместившийся в отбор: ['rows', 'count', 'value']
     * @param array $settings
     * @param string $symbol
     * @param int $skipped
     * @return array
     */
    protected static function result(array $rows, array $rest, array $settings, string $symbol, int $skipped): array
    {
        $by = (string) $settings['by'];
        $money = (string) $settings['metric'] === 'amount';

        // итог — по всему разрезу, а не только по показанным строкам
        $total = array_sum(array_column($rows, 'value')) + $rest['value'];
        $total_count = array_sum(array_column($rows, 'count')) + $rest['count'];

        $rows = array_map(fn($row) => $row + ['share' => $total > 0 ? $row['value'] / $total * 100 : 0.0], $rows);

        return [
            'by' => $by,
            'dim_label' => static::DIMENSIONS[$by][0] ?? 'Разрез',
            'metric' => $money ? 'amount' : 'count',
            'metric_label' => $money ? 'Сумма' : 'Компаний',
            'money' => $money,
            'symbol' => $symbol,
            'total' => (float) $total,
            'total_count' => (int) $total_count,
            'rest' => (int) $rest['rows'],
            'rest_count' => (int) $rest['count'],
            'rest_value' => (float) $rest['value'],
            'skipped' => $skipped,
            'rows' => array_values($rows),
        ];
    }
}
