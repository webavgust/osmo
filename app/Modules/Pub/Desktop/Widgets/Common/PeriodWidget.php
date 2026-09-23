<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Выбор периода стола (patch v30).
 *
 * Меняет контекст стола: виджеты с периодом «Со стола» пересчитываются
 * за выбранный отрезок. В низком блоке даты периода — в строку с выбором (если есть
 * место), в высоком — карточка периода (даты, сколько дней прошло), в очень высоком —
 * все периоды списком с датами, переключение нажатием.
 */
class PeriodWidget extends Widget
{
    public static function id(): string
    {
        return 'period';
    }

    public static function name(): string
    {
        return 'Период';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Период стола: виджеты «со стола» считаются за него';
    }

    public static function icon(): string
    {
        return 'fa-calendar-range';
    }

    public static function sizes(): array
    {
        return ['4x2', '8x2'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 110;
    }

    public static function ttl(): int
    {
        return 0;
    }

    /**
     * Периоды с датами, текущий период стола и его даты
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['periods' => [код => ['key', 'label', 'short', 'dates', 'days', 'passed']], 'current', 'dates']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $now = now();
        $today = $now->copy()->startOfDay();

        $periods = [];
        foreach (DesktopContext::PERIODS as $key => $label) {
            [$from, $to] = DesktopContext::range($key, $now);
            $days = $from->diffInDays($to) + 1;

            $periods[$key] = [
                'key' => $key,
                'label' => $label,
                'short' => static::caption($key, true, $now),
                'dates' => $from->format('d.m.Y') . ' – ' . $to->format('d.m.Y'),
                'days' => $days,
                // сколько дней периода уже прошло, включая сегодня
                'passed' => match (true) {
                    $today->lt($from) => 0,
                    $today->gt($to) => $days,
                    default => $from->diffInDays($today) + 1,
                },
            ];
        }

        return [
            'periods' => $periods,
            'current' => $ctx->period,
            'dates' => $periods[$ctx->period]['dates'] ?? $ctx->periodFor([])['dates'],
        ];
    }

    /**
     * Подпись периода словами: «III квартал 2026», «Сентябрь 2026», «2026 год», «Последние
     * 30 дней». Коротко (узкий блок) — без года, если он текущий, и без «Последние»:
     * «III квартал», «Сентябрь», «30 дней»
     *
     * @param string $key код из DesktopContext::PERIODS
     * @param bool $short
     * @param Carbon|null $now
     * @return string
     */
    public static function caption(string $key, bool $short = false, ?Carbon $now = null): string
    {
        $now ??= now();
        [$from] = DesktopContext::range($key, $now);
        $year = !$short || $from->year !== $now->year ? ' ' . $from->year : '';

        return match ($key) {
            'month', 'prev_month' => Str::ucfirst($from->locale('ru')->isoFormat('MMMM')) . $year,
            'quarter', 'prev_quarter' => ['I', 'II', 'III', 'IV'][$from->quarter - 1] . ' квартал' . $year,
            'year', 'prev_year' => $from->year . ' год',
            'last30' => $short ? '30 дней' : 'Последние 30 дней',
            'last90' => $short ? '90 дней' : 'Последние 90 дней',
            'last365' => $short ? '12 месяцев' : 'Последние 12 месяцев',
            default => DesktopContext::PERIODS[$key] ?? $key,
        };
    }
}
