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

    /**
     * Образцовые данные для превью: 12 последних месяцев в том же виде, что отдаёт
     * MetricRegistry::series() (подпись оси, даты отрезка), итог — сумма отрезков
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $values = [3.2, 5.1, 4.3, 7.6, 6.4, 9.1, 5.2, 6.6, 4.9, 7.1, 8.0, 9.1];
        $start = now()->startOfMonth()->subMonths(count($values) - 1);

        $rows = array_map(function ($value, $index) use ($start) {
            $from = $start->copy()->addMonths($index);

            return [
                'label' => $from->locale('ru')->isoFormat('MMM'),
                'title' => $from->format('d.m.Y') . ' — ' . $from->copy()->endOfMonth()->format('d.m.Y'),
                'value' => $value * 1000000,
            ];
        }, $values, array_keys($values));

        return [
            'label' => 'Оплаты · Факт за период, сумма',
            'unit' => 'money',
            'symbol' => '₽',
            'total' => array_sum(array_column($rows, 'value')),
            'last' => end($rows)['value'],
            'rows' => $rows,
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
