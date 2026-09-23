<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;

/**
 * Сравнение периодов (patch v30): один показатель за два отрезка и разница
 * в процентах и в абсолюте.
 *
 * Отрезки берутся из общего списка периодов стола (DesktopContext::PERIODS),
 * поэтому «этот квартал против прошлого» считается теми же правилами, что и везде.
 * Идущий месяц/квартал/год против такого же прошлого у фактического показателя
 * сравнивается по то же число: идущий — по сегодня, прошлый — по то же число
 * (III квартал по 23.09 против II квартала по 23.06), иначе прошлый целиком
 * давал бы «−100 %» в начале периода. Прогнозный показатель ('forecast' в реестре)
 * и остальные пары отрезков — целиком.
 */
class CompareWidget extends Widget
{
    /** Идущий период => такой же прошлый: такая пара сравнивается по то же число */
    public const RUNNING_PAIRS = ['month' => 'prev_month', 'quarter' => 'prev_quarter', 'year' => 'prev_year'];

    public static function id(): string
    {
        return 'compare';
    }

    public static function name(): string
    {
        return 'Сравнение периодов';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Показатель за два отрезка рядом: разница в процентах и в абсолюте';
    }

    public static function icon(): string
    {
        return 'fa-code-compare';
    }

    public static function sizes(): array
    {
        return ['8x4', '4x2', '8x2'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 40;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 600;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'metric', 'type' => 'select', 'label' => 'Показатель', 'required' => true,
                'default' => null, 'options' => fn() => MetricRegistry::periodOptions(auth()->user())],
            ['key' => 'period_a', 'type' => 'select', 'label' => 'Отрезок А', 'default' => 'quarter',
                'options' => fn() => DesktopContext::PERIODS],
            ['key' => 'period_b', 'type' => 'select', 'label' => 'Отрезок Б', 'default' => 'prev_quarter',
                'options' => fn() => DesktopContext::PERIODS],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Подпись', 'default' => '', 'hint' => 'Пусто — название показателя'],
        ];
    }

    public static function available(User $user): bool
    {
        return !empty(MetricRegistry::periodOptions($user));
    }

    public static function sourceUrl(array $settings): ?string
    {
        $metric = MetricRegistry::find((string) ($settings['metric'] ?? ''));

        return isset($metric['url']) ? call_user_func($metric['url']) : null;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        // отрезки — как у живых данных по умолчанию: этот квартал против прошлого по то же число
        [$a, $b] = static::ranges('quarter', 'prev_quarter', true);
        $point = fn(string $period, array $range, float $value, int $share) => [
            'label' => PeriodWidget::caption($period), 'until' => $range['until'],
            'dates' => $range['from']->format('d.m.Y') . ' – ' . $range['to']->format('d.m.Y'),
            'value' => $value, 'share' => $share,
        ];

        return [
            'label' => 'Выиграно за период, сумма', 'unit' => 'money', 'symbol' => $ctx->symbol($ctx->currencyFor($settings)),
            'a' => $point('quarter', $a, 48200000.0, 100),
            'b' => $point('prev_quarter', $b, 34900000.0, 72),
            'delta_percent' => 38.1, 'delta_abs' => 13300000.0,
        ];
    }

    /**
     * Показатель за оба отрезка и разница между ними
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['label', 'unit', 'symbol', 'a', 'b', 'delta_percent', 'delta_abs']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $key = (string) ($settings['metric'] ?? '');
        $metric = MetricRegistry::find($key);

        $out = [
            'label' => trim((string) $settings['caption']),
            'unit' => $metric['unit'] ?? 'count',
            'symbol' => $ctx->symbol($ctx->currencyFor($settings)),
            'a' => null,
            'b' => null,
            'delta_percent' => null,
            'delta_abs' => null,
        ];

        if (!$metric || !MetricRegistry::availableFor($key, auth()->user())) {
            return $out;
        }

        if ($out['label'] === '') {
            $out['label'] = MetricRegistry::title($key);
        }

        // фактический показатель: идущий период против прошлого — по то же число
        [$range_a, $range_b] = static::ranges((string) $settings['period_a'], (string) $settings['period_b'], empty($metric['forecast']));
        $a = static::point($key, (string) $settings['period_a'], $range_a, $ctx, $settings);
        $b = static::point($key, (string) $settings['period_b'], $range_b, $ctx, $settings);

        $max = max(abs($a['value'] ?? 0), abs($b['value'] ?? 0));
        $a['share'] = $max > 0 ? round(abs($a['value'] ?? 0) / $max * 100) : 0;
        $b['share'] = $max > 0 ? round(abs($b['value'] ?? 0) / $max * 100) : 0;

        $out['a'] = $a;
        $out['b'] = $b;

        // разница в абсолюте есть всегда, когда известны оба значения; в процентах — только
        // от ненулевой базы (с нуля рост в процентах не считается)
        if ($a['value'] !== null && $b['value'] !== null) {
            $out['delta_abs'] = $a['value'] - $b['value'];
        }
        if ($a['value'] !== null && !empty($b['value'])) {
            $out['delta_percent'] = round(($a['value'] - $b['value']) / abs($b['value']) * 100, 1);
        }

        return $out;
    }

    /**
     * Границы отрезков А и Б. Пара «идущий период — такой же прошлый» (RUNNING_PAIRS,
     * в любом порядке) при $to_date обрезается по то же число: идущий — по сегодня,
     * прошлый — DesktopContext::previousRange(..., true). В последний день периода
     * и в остальных парах — отрезки целиком
     *
     * @param string $period_a ключ из DesktopContext::PERIODS
     * @param string $period_b
     * @param bool $to_date фактический показатель
     * @return array [['from' => Carbon, 'to' => Carbon, 'until' => '23.09' | null], [...]]
     */
    public static function ranges(string $period_a, string $period_b, bool $to_date = true): array
    {
        $keys = [$period_a, $period_b];
        $out = [];
        foreach ($keys as $i => $period) {
            [$from, $to] = DesktopContext::range($period);
            $out[$i] = ['from' => $from, 'to' => $to, 'until' => null];
        }

        if (!$to_date) return $out;

        foreach ([[0, 1], [1, 0]] as [$running, $other]) {
            if ((static::RUNNING_PAIRS[$keys[$running]] ?? null) !== $keys[$other]) continue;

            $prev = DesktopContext::previousPeriod($keys[$running], true);
            // сегодня последний день периода — он уже полный, прошлый тоже целиком
            if ($prev['until'] === null) break;

            $today = now()->endOfDay();
            $out[$running]['to'] = $out[$running]['to']->copy()->min($today);
            $out[$running]['until'] = $today->format('d.m');
            $out[$other] = ['from' => $prev['from'], 'to' => $prev['to'], 'until' => $prev['until']];
            break;
        }

        return $out;
    }

    /**
     * Значение показателя за названный период
     *
     * @param string $key
     * @param string $period ключ из DesktopContext::PERIODS
     * @param array $range ['from', 'to', 'until'] из ranges()
     * @param DesktopContext $ctx
     * @param array $settings
     * @return array ['label', 'until', 'dates', 'value', 'share']
     */
    protected static function point(string $key, string $period, array $range, DesktopContext $ctx, array $settings): array
    {
        return [
            // отрезок словами: «III квартал 2026», «Август 2026», «Последние 30 дней»
            'label' => PeriodWidget::caption($period),
            // отрезок обрезан по то же число — «по 23.09»
            'until' => $range['until'],
            'dates' => $range['from']->format('d.m.Y') . ' – ' . $range['to']->format('d.m.Y'),
            'value' => MetricRegistry::value($key, $ctx, $settings, $range['from'], $range['to']),
            'share' => 0,
        ];
    }
}
