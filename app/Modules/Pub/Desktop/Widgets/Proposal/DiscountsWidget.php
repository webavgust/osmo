<?php

namespace App\Modules\Pub\Desktop\Widgets\Proposal;

use App\Modules\Pub\Analytics\Services\DiscountAnalysisService;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Services\CurrencyService;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Partner\Models\PartnerGrade;
use Illuminate\Support\Collection;

/**
 * Скидки (patch v30): средняя и максимальная скидка по КП, выделенные КП
 * и разбивка — по грейдам партнёров или по самым большим скидкам.
 *
 * Считает DiscountAnalysisService — тот же сервис, что рисует страницу «Анализ скидок»,
 * поэтому цифры сходятся с ней один в один: скидка двухступенчатая (заказчику, потом
 * партнёру от уже уменьшенной цены) и берётся по последнему созданному варианту
 * последней редакции КП. Средняя скидка средневзвешенная по прайсу: одно мелкое КП
 * с большой скидкой не задирает общий уровень.
 *
 * Отрезок: год отправки (как на странице) или период стола — тогда выборка года
 * дополнительно режется по дате отправки, а средние по грейдам и пометки
 * пересчитываются по тому, что осталось.
 *
 * Пороги — константы портала: discount_hard_limit_p (потолок совокупной скидки)
 * и discount_grade_alert_pp (превышение среднего по грейду). Суммы скидок сервис
 * считает в рублях, поэтому в валюту стола они переводятся курсом на сегодня;
 * курса нет — показываем рубли.
 */
class DiscountsWidget extends Widget
{
    /** Разбивка суммы скидок */
    public const SPLITS = ['grade' => 'По грейдам партнёров', 'top' => 'КП с самой большой скидкой', 'none' => 'Без разбивки'];

    public static function id(): string { return 'discounts'; }

    public static function name(): string { return 'Скидки'; }

    public static function category(): string { return 'proposal'; }

    public static function description(): string
    {
        return 'Средняя и максимальная скидка по КП, выделенные КП и разбивка по грейдам';
    }

    public static function icon(): string { return 'fa-tags'; }

    public static function sizes(): array { return ['8x4', '16x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 170; }

    public static function usesPeriod(): bool { return true; }

    public static function usesCurrency(): bool { return true; }

    /** выборка тяжёлая: тянет все позиции всех вариантов за год */
    public static function ttl(): int { return 1800; }

    public static function fields(): array
    {
        return [
            ['key' => 'range', 'type' => 'select', 'label' => 'Отрезок', 'default' => 'year',
                'options' => ['year' => 'Год', 'period' => 'Период стола или свой'],
                'hint' => 'Отбор по дате отправки КП, как на странице анализа скидок'],
            ['key' => 'year', 'type' => 'select', 'label' => 'Год', 'default' => 'auto',
                'options' => fn() => ['auto' => 'Последний с данными'] + collect(DiscountAnalysisService::years())
                    ->mapWithKeys(fn($year) => [(string) $year => (string) $year])
                    ->all()],
            ['key' => 'split', 'type' => 'select', 'label' => 'Разбивка', 'default' => 'grade', 'options' => static::SPLITS,
                'hint' => 'Видна в высоком блоке'],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько КП в разбивке', 'default' => 10, 'min' => 3, 'max' => 30],
            ['key' => 'show_sum', 'type' => 'bool', 'label' => 'Сумма отданных скидок', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $year = static::year($settings);

        return route('analytics.discounts', $year === null ? [] : ['year' => $year]);
    }

    /**
     * Год выборки: «Последний с данными» — как год по умолчанию на странице
     *
     * @param array $settings
     * @return int|null
     */
    public static function year(array $settings): ?int
    {
        $year = (string) ($settings['year'] ?? 'auto');
        if ($year !== 'auto') return (int) $year;

        $years = DiscountAnalysisService::years();

        return empty($years) ? null : (int) $years[0];
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $split = (string) ($settings['split'] ?? 'grade');
        $rows = [];

        if ($split === 'top') {
            // КП с самой большой скидкой — столько, сколько задано в настройке
            $companies = ['ООО «Альфа»', 'АО «Бета-Системс»', 'ООО «Гамма Интеграция»', 'ПАО «Дельта»', 'ООО «Эпсилон»'];
            for ($i = 0; $i < (int) ($settings['limit'] ?? 10); $i++) {
                $value = round(70.3 - $i * 1.9, 1);
                $rows[] = [
                    'key' => (string) (1024 + $i),
                    'label' => '№ ' . (1024 + $i) . ' · Лицензии и внедрение ' . $companies[$i % 5],
                    'sub' => $companies[($i + 2) % 5],
                    'value' => $value,
                    'count' => null,
                    'url' => null,
                    'alert' => $value > 40,
                    'alert_text' => $value > 40 ? 'Выше потолка 40 %' : '',
                ];
            }
        } else {
            $sample = [['platinum', 21.4, 12], ['gold', 18.3, 9], ['silver', 46.6, 6], ['bronze', 36.0, 4],
                ['vendor', 14.2, 2], ['agent', 10.9, 3], ['', 8.5, 19]];

            foreach ($sample as [$key, $value, $count]) {
                $grade = PartnerGrade::tryFrom($key)?->data();
                $rows[] = [
                    'key' => $key,
                    'label' => (string) ($grade['label'] ?? 'Без грейда'),
                    'sub' => (string) ($grade['description'] ?? 'Партнёр не указан или без грейда'),
                    'value' => $value,
                    'count' => $count,
                    'url' => null,
                    'alert' => $value > 40,
                    'alert_text' => $value > 40 ? 'Выше потолка 40 %' : '',
                ];
            }
        }

        return [
            'label' => '2026 год',
            'count' => 55,
            'average' => 24.7,
            'max' => 70.3,
            'max_label' => 'КП 1024 · ООО «Альфа»',
            'alerts' => 19,
            'hard_limit' => 40,
            'grade_alert_pp' => 5,
            'amount' => 384600000.0,
            'symbol' => '₽',
            'split' => $split,
            'split_label' => $split === 'top' ? 'КП' : 'грейд',
            'rows' => $rows,
        ];
    }

    /**
     * Средняя и максимальная скидка, выделенные КП и разбивка
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['label', 'count', 'average', 'max', 'max_label', 'alerts', 'hard_limit',
     *     'grade_alert_pp', 'amount', 'symbol', 'split', 'split_label',
     *     'rows' => [['key', 'label', 'sub', 'value', 'count', 'url', 'alert', 'alert_text']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $period = $ctx->periodFor($settings);
        $by_period = (string) $settings['range'] === 'period';
        $rows = static::selection($settings, $period, $by_period);

        $totals = DiscountAnalysisService::totals($rows);
        $top = $rows->sortByDesc('total_p')->first();
        $split = (string) $settings['split'];

        return [
            'label' => $by_period ? $period['label'] : (($year = static::year($settings)) === null ? 'все КП' : $year . ' год'),
            'count' => $rows->count(),
            'average' => round((float) $totals['total_p'], 1),
            'max' => $top === null ? null : round((float) $top['total_p'], 1),
            'max_label' => $top === null ? '' : static::proposalLabel($top),
            'alerts' => (int) $totals['alerts'],
            'hard_limit' => DiscountAnalysisService::hardLimitP(),
            'grade_alert_pp' => DiscountAnalysisService::gradeAlertPp(),
            'amount' => !empty($settings['show_sum'])
                ? round(static::amount($totals, $ctx->currencyFor($settings)), 2)
                : null,
            'symbol' => $ctx->symbol(static::currency($ctx->currencyFor($settings))),
            'split' => $split,
            'split_label' => $split === 'top' ? 'КП' : 'грейд',
            'rows' => match ($split) {
                'grade' => static::byGrade($rows),
                'top' => static::byProposal($rows, (int) $settings['limit']),
                default => [],
            },
        ];
    }

    /**
     * Выборка КП со скидками: за год или за период стола
     *
     * @param array $settings
     * @param array $period
     * @param bool $by_period
     * @return Collection строки DiscountAnalysisService
     */
    protected static function selection(array $settings, array $period, bool $by_period): Collection
    {
        if (!$by_period) {
            $year = static::year($settings);

            return DiscountAnalysisService::rows($year === null ? [] : ['year' => $year]);
        }

        // период может задеть два года — берём оба и режем по датам
        $rows = collect();
        for ($year = (int) $period['from']->year; $year <= (int) $period['to']->year; $year++) {
            $rows = $rows->concat(DiscountAnalysisService::rows(['year' => $year]));
        }

        $from = $period['from']->copy()->startOfDay();
        $to = $period['to']->copy()->endOfDay();
        $rows = $rows->filter(function ($row) use ($from, $to) {
            $date = $row['proposal']->sended_at;

            return $date !== null && $date->between($from, $to);
        })->values();

        // выборка изменилась — средние по грейдам и пометки считаем заново по ней
        $averages = DiscountAnalysisService::gradeAverages($rows);

        return $rows->map(fn($row) => DiscountAnalysisService::flag($row, $averages))->values();
    }

    /**
     * Средняя скидка по грейдам партнёров
     *
     * @param Collection $rows
     * @return array
     */
    protected static function byGrade(Collection $rows): array
    {
        $averages = DiscountAnalysisService::gradeAverages($rows);
        $limit = DiscountAnalysisService::hardLimitP();
        $ret = [];

        foreach ($rows->groupBy('grade_key') as $key => $group) {
            $grade = PartnerGrade::tryFrom((string) $key)?->data();
            $value = round((float) ($averages[(string) $key] ?? 0), 1);

            $ret[] = [
                'key' => (string) $key,
                'label' => (string) ($grade['label'] ?? 'Без грейда'),
                'sub' => (string) ($grade['description'] ?? 'Партнёр не указан или без грейда'),
                'value' => $value,
                'count' => $group->count(),
                'url' => null,
                'alert' => $value > $limit,
                'alert_text' => $value > $limit ? 'Средняя скидка выше потолка ' . $limit . ' %' : '',
            ];
        }

        usort($ret, fn($a, $b) => $b['value'] <=> $a['value']);

        return $ret;
    }

    /**
     * КП с самыми большими скидками
     *
     * @param Collection $rows
     * @param int $limit
     * @return array
     */
    protected static function byProposal(Collection $rows, int $limit): array
    {
        return $rows->sortByDesc('total_p')
            ->take($limit)
            ->map(fn($row) => [
                'key' => (string) $row['proposal']->group,
                'label' => static::proposalLabel($row),
                'sub' => (string) ($row['partner']->name ?? $row['company']->name ?? ''),
                'value' => round((float) $row['total_p'], 1),
                'count' => null,
                'url' => route('proposal.detail', [$row['proposal']->group, $row['proposal']->iteration]),
                'alert' => !empty($row['alerts']),
                'alert_text' => implode('; ', array_values($row['alerts'] ?? [])),
            ])
            ->values()
            ->all();
    }

    /**
     * Подпись КП: номер и название
     *
     * @param array $row строка DiscountAnalysisService
     * @return string
     */
    protected static function proposalLabel(array $row): string
    {
        $number = trim((string) $row['proposal']->number);

        return trim(($number !== '' ? '№ ' . $number . ' · ' : '') . (string) $row['proposal']->name);
    }

    /**
     * Сумма отданных скидок в валюте стола: сервис считает её в рублях
     *
     * @param array $totals
     * @param string $currency
     * @return float
     */
    protected static function amount(array $totals, string $currency): float
    {
        $amount = (float) $totals['customer'] + (float) $totals['partner'];

        if ($currency === Currency::CURRENCY_DEFAULT) return $amount;

        $rate = CurrencyService::getConvertRateForDate(now(), Currency::CURRENCY_DEFAULT, $currency);

        return $rate === null ? $amount : $amount * $rate;
    }

    /**
     * Валюта показа: та, в которую удалось пересчитать рублёвые суммы сервиса
     *
     * @param string $currency
     * @return string
     */
    protected static function currency(string $currency): string
    {
        if ($currency === Currency::CURRENCY_DEFAULT) return $currency;

        return CurrencyService::getConvertRateForDate(now(), Currency::CURRENCY_DEFAULT, $currency) === null
            ? Currency::CURRENCY_DEFAULT
            : $currency;
    }
}
