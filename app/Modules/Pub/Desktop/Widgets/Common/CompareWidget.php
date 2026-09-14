<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;

/**
 * Сравнение периодов (patch v30): один показатель за два отрезка и разница
 * в процентах и в абсолюте.
 *
 * Отрезки берутся из общего списка периодов стола (DesktopContext::PERIODS),
 * поэтому «этот квартал против прошлого» считается теми же правилами, что и везде.
 */
class CompareWidget extends Widget
{
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
        return [
            'label' => 'Выиграно за период, сумма', 'unit' => 'money', 'symbol' => '₽',
            'a' => ['label' => 'III квартал 2026', 'value' => 48200000.0, 'share' => 100],
            'b' => ['label' => 'II квартал 2026', 'value' => 34900000.0, 'share' => 72],
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

        $a = static::point($key, (string) $settings['period_a'], $ctx, $settings);
        $b = static::point($key, (string) $settings['period_b'], $ctx, $settings);

        $max = max(abs($a['value'] ?? 0), abs($b['value'] ?? 0));
        $a['share'] = $max > 0 ? round(abs($a['value'] ?? 0) / $max * 100) : 0;
        $b['share'] = $max > 0 ? round(abs($b['value'] ?? 0) / $max * 100) : 0;

        $out['a'] = $a;
        $out['b'] = $b;

        if ($a['value'] !== null && !empty($b['value'])) {
            $out['delta_abs'] = $a['value'] - $b['value'];
            $out['delta_percent'] = round(($a['value'] - $b['value']) / abs($b['value']) * 100, 1);
        }

        return $out;
    }

    /**
     * Значение показателя за названный период
     *
     * @param string $key
     * @param string $period ключ из DesktopContext::PERIODS
     * @param DesktopContext $ctx
     * @param array $settings
     * @return array ['label', 'value', 'share']
     */
    protected static function point(string $key, string $period, DesktopContext $ctx, array $settings): array
    {
        [$from, $to] = DesktopContext::range($period);

        return [
            'label' => static::periodLabel($period, $from, $to),
            'value' => MetricRegistry::value($key, $ctx, $settings, $from, $to),
            'share' => 0,
        ];
    }

    /**
     * Подпись отрезка: название периода плюс годы, если отрезок не в этом году
     *
     * @param string $period
     * @param Carbon $from
     * @param Carbon $to
     * @return string
     */
    protected static function periodLabel(string $period, Carbon $from, Carbon $to): string
    {
        $label = DesktopContext::PERIODS[$period] ?? $period;

        return $from->year === $to->year ? $label . ' ' . $from->year : $label;
    }
}
