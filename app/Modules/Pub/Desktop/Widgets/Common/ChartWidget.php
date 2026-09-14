<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Metrics\MetricRegistry;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;

/**
 * График показателя (patch v30): любой показатель реестра по отрезкам времени.
 *
 * Ряд считает MetricRegistry::series() — значение показателя за каждый отрезок
 * (день, неделя, месяц, квартал). Показатели «на сейчас» (без периода) в список
 * не попадают: у них ряда нет.
 */
class ChartWidget extends Widget
{
    public static function id(): string
    {
        return 'chart';
    }

    public static function name(): string
    {
        return 'График показателя';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Показатель по дням, неделям, месяцам или кварталам — линия, столбцы или область';
    }

    public static function icon(): string
    {
        return 'fa-chart-line';
    }

    public static function sizes(): array
    {
        return ['8x4', '8x8', '16x8', '32x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 30;
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
            ['key' => 'kind', 'type' => 'select', 'label' => 'Вид', 'default' => 'bar',
                'options' => ['bar' => 'Столбцы', 'line' => 'Линия', 'area' => 'Область']],
            ['key' => 'step', 'type' => 'select', 'label' => 'Шаг', 'default' => 'month',
                'options' => fn() => collect(MetricRegistry::STEPS)->map(fn($step) => $step[0])->all()],
            ['key' => 'count', 'type' => 'number', 'label' => 'Сколько отрезков', 'default' => 12, 'min' => 2, 'max' => 36],
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
            'label' => 'Оплаты факт по месяцам',
            'unit' => 'money',
            'symbol' => '₽',
            'total' => 68400000.0,
            'last' => 9100000.0,
            'rows' => collect(['окт', 'ноя', 'дек', 'янв', 'фев', 'мар', 'апр', 'май', 'июн', 'июл', 'авг', 'сен'])
                ->zip([3.2, 5.1, 4.3, 7.6, 6.4, 9.1, 5.2, 6.6, 4.9, 7.1, 8.0, 9.1])
                ->map(fn($pair, $index) => ['label' => $pair[0], 'title' => $pair[0] . ($index < 3 ? ' 2025' : ' 2026'), 'value' => $pair[1] * 1000000])
                ->all(),
        ];
    }

    /**
     * Ряд показателя по отрезкам
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['label', 'unit', 'symbol', 'total', 'last', 'rows' => [['label', 'title', 'value']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $key = (string) ($settings['metric'] ?? '');
        $metric = MetricRegistry::find($key);

        if (!$metric || !MetricRegistry::availableFor($key, auth()->user())) {
            return ['label' => '', 'unit' => 'count', 'symbol' => $ctx->symbol(), 'total' => null, 'last' => null, 'rows' => []];
        }

        $rows = MetricRegistry::series($key, $ctx, $settings, (string) $settings['step'], (int) $settings['count']);
        $values = array_column($rows, 'value');

        return [
            'label' => trim((string) $settings['caption']) !== '' ? trim((string) $settings['caption']) : MetricRegistry::title($key),
            'unit' => $metric['unit'] ?? 'count',
            'symbol' => $ctx->symbol($ctx->currencyFor($settings)),
            'total' => $values ? array_sum($values) : null,
            'last' => $values ? end($values) : null,
            'rows' => $rows,
        ];
    }
}
