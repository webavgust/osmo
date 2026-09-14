<?php

namespace App\Modules\Pub\Desktop\Widgets\Personal;

use App\Facades\Tools;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;

/**
 * Обратный отсчёт (patch v30): сколько дней до даты — своей или до конца
 * месяца, квартала, года.
 *
 * В день события — «сегодня», после — «прошло N дней». Когда до события меньше
 * порога, число становится красным.
 */
class CountdownWidget extends Widget
{
    /** Что отсчитываем */
    public const TARGETS = [
        'date' => 'Своя дата',
        'month' => 'До конца месяца',
        'quarter' => 'До конца квартала',
        'year' => 'До конца года',
    ];

    public static function id(): string
    {
        return 'countdown';
    }

    public static function name(): string
    {
        return 'Обратный отсчёт';
    }

    public static function category(): string
    {
        return 'personal';
    }

    public static function description(): string
    {
        return 'Дней до дедлайна, конца квартала или своей даты';
    }

    public static function icon(): string
    {
        return 'fa-hourglass-half';
    }

    public static function sizes(): array
    {
        return ['4x2', '2x2', '4x4'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 80;
    }

    public static function ttl(): int
    {
        return 0;
    }

    public static function available(User $user): bool
    {
        return true;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'target', 'type' => 'select', 'label' => 'Событие', 'default' => 'quarter', 'options' => static::TARGETS],
            ['key' => 'date', 'type' => 'text', 'label' => 'Дата', 'default' => '', 'hint' => 'ДД.ММ.ГГГГ — для события «Своя дата»'],
            ['key' => 'caption', 'type' => 'text', 'label' => 'Подпись', 'default' => '', 'hint' => 'Пусто — подпись по событию'],
            ['key' => 'warn_days', 'type' => 'number', 'label' => 'Тревожный порог, дней', 'default' => 7, 'min' => 0, 'max' => 365],
        ];
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return ['days' => 16, 'passed' => false, 'today' => false, 'caption' => 'дней до конца квартала', 'date' => '30.09.2026', 'warn' => false, 'progress' => 82, 'period' => 'квартала'];
    }

    /**
     * Сколько дней осталось до события
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['days', 'passed', 'today', 'caption', 'date', 'warn', 'progress' (% прошедшего отрезка или null), 'period']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $today = now()->startOfDay();

        // у месяца, квартала и года есть начало — по нему считается, какая доля отрезка прошла
        [$target, $default_caption, $start, $period] = match ((string) $settings['target']) {
            'month' => [now()->endOfMonth()->startOfDay(), 'до конца месяца', now()->startOfMonth(), 'месяца'],
            'year' => [now()->endOfYear()->startOfDay(), 'до конца года', now()->startOfYear(), 'года'],
            'date' => [static::parseDate((string) $settings['date']), 'до события', null, null],
            default => [now()->endOfQuarter()->startOfDay(), 'до конца квартала', now()->startOfQuarter(), 'квартала'],
        };

        if (!$target) {
            return ['days' => null, 'passed' => false, 'today' => false, 'caption' => 'дата не задана', 'date' => null, 'warn' => false, 'progress' => null, 'period' => null];
        }

        $progress = $start
            ? (int) min(100, round($start->diffInDays($today) / max(1, $start->diffInDays($target) + 1) * 100))
            : null;

        $days = $today->diffInDays($target, false);
        $caption = trim((string) $settings['caption']);

        if ($caption === '') {
            $caption = $days < 0
                ? Tools::morph(abs($days), 'день', 'дня', 'дней') . ' назад'
                : Tools::morph(max($days, 0), 'день', 'дня', 'дней') . ' ' . $default_caption;
        }

        return [
            'days' => abs($days),
            'passed' => $days < 0,
            'today' => $days === 0,
            'caption' => $caption,
            'date' => $target->format('d.m.Y'),
            'warn' => $days >= 0 && $days <= (int) $settings['warn_days'],
            'progress' => $progress,
            'period' => $period,
        ];
    }

    /**
     * Дата из настройки: ДД.ММ.ГГГГ или ГГГГ-ММ-ДД
     *
     * @param string $value
     * @return Carbon|null
     */
    protected static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        foreach (['d.m.Y', 'Y-m-d', 'd.m.y'] as $format) {
            $date = Carbon::createFromFormat($format, $value);
            if ($date !== false) {
                return $date->startOfDay();
            }
        }

        return null;
    }
}
