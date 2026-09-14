<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;

/**
 * Прогресс к цели (patch v30): факт против плана в процентах.
 *
 * Цель — либо число в настройке, либо константа портала (`consts`): тогда план
 * задаёт админ в одном месте и все столы считают одинаково.
 */
class ProgressWidget extends Widget
{
    /** Пороги цвета: до 50 % серый, до 90 % жёлтый, дальше зелёный */
    public const THRESHOLDS = [50 => 'gray-500', 90 => 'warning'];

    public static function id(): string
    {
        return 'progress';
    }

    public static function name(): string
    {
        return 'Прогресс к цели';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Факт против плана: процент, шкала и остаток до цели';
    }

    public static function icon(): string
    {
        return 'fa-bullseye-arrow';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x2', '4x4', '8x4'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 35;
    }

    public static function usesCurrency(): bool
    {
        return true;
    }

    public static function usesPeriod(): bool
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
                'default' => null, 'options' => fn() => MetricRegistry::options(auth()->user())],
            ['key' => 'goal_source', 'type' => 'select', 'label' => 'Цель', 'default' => 'number',
                'options' => ['number' => 'Число', 'const' => 'Константа портала']],
            ['key' => 'goal', 'type' => 'number', 'label' => 'Значение цели', 'default' => 0, 'min' => 0],
            ['key' => 'goal_const', 'type' => 'text', 'label' => 'Ключ константы', 'default' => '',
                'hint' => 'Например plan_quarter — значение берётся из констант портала'],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Подпись', 'default' => '', 'hint' => 'Пусто — название показателя'],
            ['key' => 'ring', 'type' => 'bool', 'label' => 'Кольцо вместо шкалы', 'default' => false,
                'hint' => 'Кольцо рисуется, если блок достаточно высокий'],
        ];
    }

    public static function available(User $user): bool
    {
        return !empty(MetricRegistry::options($user));
    }

    public static function sourceUrl(array $settings): ?string
    {
        $metric = MetricRegistry::find((string) ($settings['metric'] ?? ''));

        return isset($metric['url']) ? call_user_func($metric['url']) : null;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return [
            'label' => 'План квартала', 'unit' => 'money', 'symbol' => '₽',
            'value' => 38400000.0, 'goal' => 60000000.0, 'percent' => 64.0, 'left' => 21600000.0,
            'color' => 'warning', 'period_label' => 'III квартал',
        ];
    }

    /**
     * Факт, цель и процент исполнения
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['label', 'unit', 'symbol', 'value', 'goal', 'percent', 'left', 'color', 'period_label']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $key = (string) ($settings['metric'] ?? '');
        $metric = MetricRegistry::find($key);
        $period = $ctx->periodFor($settings);

        $out = [
            'label' => trim((string) $settings['caption']),
            'unit' => $metric['unit'] ?? 'count',
            'symbol' => $ctx->symbol($ctx->currencyFor($settings)),
            'value' => null,
            'goal' => null,
            'percent' => null,
            'left' => null,
            'color' => 'primary',
            'period_label' => empty($metric['period']) ? '' : $period['label'],
        ];

        if (!$metric || !MetricRegistry::availableFor($key, auth()->user())) {
            return $out;
        }

        if ($out['label'] === '') {
            $out['label'] = MetricRegistry::title($key);
        }

        $out['value'] = MetricRegistry::value($key, $ctx, $settings);
        $out['goal'] = $settings['goal_source'] === 'const'
            ? Constant::float(trim((string) $settings['goal_const']))
            : (float) $settings['goal'];

        if ($out['goal'] === null || $out['goal'] <= 0 || $out['value'] === null) {
            return $out;
        }

        $percent = $out['value'] / $out['goal'] * 100;
        $out['percent'] = round($percent, 1);
        $out['left'] = max(0.0, $out['goal'] - $out['value']);
        $out['color'] = 'success';

        foreach (static::THRESHOLDS as $limit => $color) {
            if ($percent < $limit) {
                $out['color'] = $color;
                break;
            }
        }

        return $out;
    }
}
