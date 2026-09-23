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
 * Число — полные дни после сегодняшнего до последнего дня отрезка (23.09 → 30.09 — 7 дней
 * до конца III квартала); в сам последний день — «сегодня» и «последний день квартала»,
 * после своей даты — «N дней назад». Когда до события не больше порога, число красное.
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

    /**
     * Образец для превью: настоящий отсчёт до конца текущего квартала
     * (застывшие «16 дней до 30.09.2026» врали бы уже через день)
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        return $this->data(['target' => 'quarter', 'caption' => ''] + $settings, $ctx);
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

        // у месяца, квартала и года есть начало — по нему считается, какая доля отрезка прошла;
        // цель — последний день отрезка, в этот день вместо числа «сегодня» и своя подпись
        [$target, $default_caption, $start, $period, $last_day] = match ((string) $settings['target']) {
            'month' => [now()->endOfMonth()->startOfDay(), 'до конца месяца', now()->startOfMonth(), 'месяца', 'последний день месяца'],
            'year' => [now()->endOfYear()->startOfDay(), 'до конца года', now()->startOfYear(), 'года', 'последний день года'],
            'date' => [static::parseDate((string) $settings['date']), 'до события', null, null, 'день события'],
            default => [now()->endOfQuarter()->startOfDay(), 'до конца квартала', now()->startOfQuarter(), 'квартала', 'последний день квартала'],
        };

        if (!$target) {
            return ['days' => null, 'passed' => false, 'today' => false, 'caption' => 'дата не задана', 'date' => null, 'warn' => false, 'progress' => null, 'period' => null, 'hint' => null];
        }

        $progress = $start
            ? (int) min(100, round($start->diffInDays($today) / max(1, $start->diffInDays($target) + 1) * 100))
            : null;

        // полные дни после сегодняшнего: 23.09 → 30.09 = 7, в сам последний день — 0 («сегодня»)
        $days = $today->diffInDays($target, false);
        $caption = trim((string) $settings['caption']);
        $morph = fn(int $n) => $n . ' ' . Tools::morph($n, 'день', 'дня', 'дней');

        if ($caption === '') {
            $caption = match (true) {
                $days < 0 => Tools::morph(abs($days), 'день', 'дня', 'дней') . ' назад',
                // было «сегодня / дней до конца квартала»
                $days === 0 => $last_day,
                default => Tools::morph($days, 'день', 'дня', 'дней') . ' ' . $default_caption,
            };
        }

        // подсказка блока: какой день считается концом и считается ли сегодняшний
        $hint = $target->format('d.m.Y') . ' — ' . $last_day . match (true) {
            $days < 0 => ', прошло ' . $morph(abs($days)),
            $days === 0 => ', сегодня',
            default => '. Не считая сегодняшнего, осталось ' . $morph($days),
        };

        return [
            'days' => abs($days),
            'passed' => $days < 0,
            'today' => $days === 0,
            'caption' => $caption,
            'date' => $target->format('d.m.Y'),
            'warn' => $days >= 0 && $days <= (int) $settings['warn_days'],
            'progress' => $progress,
            'period' => $period,
            'hint' => $hint,
        ];
    }

    /**
     * Дата из настройки: ДД.ММ.ГГГГ, ДД.ММ.ГГ или ГГГГ-ММ-ДД; остальное — null («Укажите дату»).
     *
     * Разбор строгий: Carbon::createFromFormat на чужом формате бросает исключение
     * (виджет падал), а «31.12.26» по формату d.m.Y читал как 26-й год нашей эры
     *
     * @param string $value
     * @return Carbon|null
     */
    protected static function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        if (preg_match('/^(\d{1,2})\.(\d{1,2})\.(\d{4}|\d{2})$/', $value, $m)) {
            [, $day, $month, $year] = $m;
            if (strlen($year) === 2) $year = '20' . $year;
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $value, $m)) {
            [, $year, $month, $day] = $m;
        } else {
            return null;
        }

        if (!checkdate((int) $month, (int) $day, (int) $year)) {
            return null;
        }

        return Carbon::create((int) $year, (int) $month, (int) $day)->startOfDay();
    }
}
