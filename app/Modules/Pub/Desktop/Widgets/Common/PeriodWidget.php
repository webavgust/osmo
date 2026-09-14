<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Выбор периода стола (patch v30).
 *
 * Меняет контекст стола: виджеты с периодом «Со стола» пересчитываются
 * за выбранный отрезок. Под списком (на ширине 8 — справа) — даты периода.
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
     * Периоды, текущий период стола и его даты
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['periods' => [код => подпись], 'current', 'dates']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        return [
            'periods' => DesktopContext::PERIODS,
            'current' => $ctx->period,
            'dates' => $ctx->periodFor([])['dates'],
        ];
    }
}
