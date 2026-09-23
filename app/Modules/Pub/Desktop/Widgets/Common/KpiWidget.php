<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Число (patch v30): один показатель из MetricRegistry крупно. Для показателя
 * с периодом — сравнение с прошлым таким же периодом: у фактического показателя
 * идущий месяц/квартал/год сравнивается с прошлым по то же число, у прогнозного
 * ('forecast' в реестре) — с прошлым целиком (DesktopContext::previousRange()).
 */
class KpiWidget extends Widget
{
    public static function id(): string { return 'kpi'; }

    public static function name(): string { return 'Число'; }

    public static function category(): string { return 'common'; }

    public static function description(): string
    {
        return 'Один показатель крупно: КП в работе, выигрыши за период, конверсия, партнёры';
    }

    public static function icon(): string { return 'fa-hashtag'; }

    public static function sizes(): array { return ['4x2', '2x2', '4x4', '8x4']; }

    public static function defaultSize(): string { return '4x2'; }

    public static function order(): int { return 10; }

    // название показателя уже выводится в теле — заголовок «Число» по умолчанию не нужен
    public static function showTitle(): bool { return false; }

    public static function usesCurrency(): bool { return true; }

    public static function usesPeriod(): bool { return true; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель', 'required' => true, 'default' => 'proposals.in_work_sum',
                'options' => fn() => MetricRegistry::options(auth()->user())],
            ['key' => 'compare', 'type' => 'bool', 'label' => 'Сравнить с прошлым периодом', 'default' => true, 'hint' => 'Для показателей с периодом'],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Подпись под числом', 'default' => '', 'max' => 60, 'hint' => 'Пусто — название показателя'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        $metric = MetricRegistry::find((string) ($settings['metric'] ?? ''));

        return isset($metric['url']) ? call_user_func($metric['url']) : null;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $months = ['окт', 'ноя', 'дек', 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен'];
        $values = [18.4, 19.9, 17.2, 20.8, 23.1, 22.4, 24.0, 21.5, 23.6, 22.9, 22.6, 21.7];

        return [
            'value' => 21700000.0, 'previous' => 22600000.0, 'delta_percent' => -4.0, 'unit' => 'money', 'symbol' => '₽',
            'prev_dates' => DesktopContext::previousPeriod('quarter', true)['dates'],
            // образец — показатель с периодом: у него есть ряд для спарклайна и подпись периода, как у живых данных
            'label' => 'КП · Выиграно за период, сумма', 'period_label' => 'Текущий квартал', 'url' => null,
            'series' => array_map(fn($month, $value) => ['label' => $month, 'title' => $month . ' 2026', 'value' => $value * 1000000], $months, $values),
        ];
    }

    /**
     * Значение показателя, прошлое значение и ряд за 12 месяцев (для спарклайна)
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['value', 'previous', 'delta_percent', 'prev_dates', 'unit', 'symbol', 'label', 'period_label', 'url',
     *     'series' => [['label', 'title', 'value']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $key = (string) $settings['metric'];
        $metric = MetricRegistry::find($key);

        $out = [
            'value' => null, 'previous' => null, 'delta_percent' => null, 'prev_dates' => null,
            'unit' => $metric['unit'] ?? 'count',
            'symbol' => $ctx->symbol($ctx->currencyFor($settings)),
            'label' => MetricRegistry::title($key),
            'period_label' => null,
            'url' => static::sourceUrl($settings),
            'series' => [],
        ];

        if ($metric === null || !MetricRegistry::availableFor($key, $ctx->user)) {
            return $out;
        }

        if (empty($metric['period'])) {
            $out['value'] = MetricRegistry::value($key, $ctx, $settings);

            return $out;
        }

        $period = $ctx->periodFor($settings);
        $out['period_label'] = $period['label'];
        $out['value'] = MetricRegistry::value($key, $ctx, $settings, $period['from'], $period['to']);
        // ряд за 12 месяцев — спарклайн в высоком блоке
        $out['series'] = MetricRegistry::series($key, $ctx, $settings, 'month', 12);

        if ($settings['compare']) {
            // факт идущего периода — к прошлому по то же число, прогноз — к прошлому целиком
            $prev = DesktopContext::previousPeriod($period['key'], empty($metric['forecast']));
            $out['prev_dates'] = $prev['dates'];
            $out['previous'] = MetricRegistry::value($key, $ctx, $settings, $prev['from'], $prev['to']);

            if ($out['value'] !== null && !empty($out['previous'])) {
                $out['delta_percent'] = round(($out['value'] - $out['previous']) / abs($out['previous']) * 100, 1);
            }
        }

        return $out;
    }
}
