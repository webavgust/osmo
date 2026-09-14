<?php

namespace App\Modules\Pub\Desktop\Widgets\Personal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Carbon;

/**
 * Часы (patch v30): местное время партнёров — чтобы не звонить в Китай среди ночи.
 *
 * До четырёх поясов. Сервер рисует время на момент отрисовки, дальше его ведёт
 * браузер (elements с data-desk-clock, tickClocks в osmo-desktop.js).
 */
class ClockWidget extends Widget
{
    /** Часовые пояса на выбор: IANA => подпись */
    public const ZONES = [
        'Europe/Moscow' => 'Москва',
        'Europe/Kaliningrad' => 'Калининград',
        'Asia/Yekaterinburg' => 'Екатеринбург',
        'Asia/Novosibirsk' => 'Новосибирск',
        'Asia/Vladivostok' => 'Владивосток',
        'Asia/Shanghai' => 'Пекин',
        'Asia/Hong_Kong' => 'Гонконг',
        'Asia/Tashkent' => 'Ташкент',
        'Asia/Almaty' => 'Алматы',
        'Asia/Baku' => 'Баку',
        'Asia/Yerevan' => 'Ереван',
        'Asia/Tbilisi' => 'Тбилиси',
        'Asia/Dubai' => 'Дубай',
        'Europe/Belgrade' => 'Белград',
        'Europe/Berlin' => 'Берлин',
        'Europe/London' => 'Лондон',
        'America/New_York' => 'Нью-Йорк',
    ];

    public static function id(): string
    {
        return 'clock';
    }

    public static function name(): string
    {
        return 'Часы';
    }

    public static function category(): string
    {
        return 'personal';
    }

    public static function description(): string
    {
        return 'Местное время в городах партнёров — до четырёх поясов';
    }

    public static function icon(): string
    {
        return 'fa-clock';
    }

    public static function sizes(): array
    {
        return ['2x2', '4x2', '8x2'];
    }

    public static function defaultSize(): string
    {
        return '2x2';
    }

    public static function order(): int
    {
        return 70;
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
            ['key' => 'zone_1', 'type' => 'select', 'label' => 'Первый пояс', 'default' => 'Europe/Moscow', 'options' => static::ZONES],
            ['key' => 'zone_2', 'type' => 'select', 'label' => 'Второй пояс', 'default' => 'none', 'options' => ['none' => 'Нет'] + static::ZONES],
            ['key' => 'zone_3', 'type' => 'select', 'label' => 'Третий пояс', 'default' => 'none', 'options' => ['none' => 'Нет'] + static::ZONES],
            ['key' => 'zone_4', 'type' => 'select', 'label' => 'Четвёртый пояс', 'default' => 'none', 'options' => ['none' => 'Нет'] + static::ZONES],
            ['key' => 'format', 'type' => 'select', 'label' => 'Формат', 'default' => '24', 'options' => ['24' => '24 часа', '12' => '12 часов']],
            ['key' => 'date', 'type' => 'bool', 'label' => 'Показывать дату', 'default' => false],
        ];
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return ['rows' => [
            ['zone' => 'Europe/Moscow', 'city' => 'Москва', 'time' => '14:35', 'date' => '14 сентября', 'shift' => ''],
            ['zone' => 'Asia/Shanghai', 'city' => 'Пекин', 'time' => '19:35', 'date' => '14 сентября', 'shift' => '+5 ч'],
        ]];
    }

    /**
     * Время по выбранным поясам на момент отрисовки
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['zone', 'city', 'time', 'date', 'shift']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $format = $settings['format'] === '12' ? 'g:i a' : 'H:i';
        $own = now()->utcOffset();
        $rows = [];

        foreach (['zone_1', 'zone_2', 'zone_3', 'zone_4'] as $key) {
            $zone = (string) ($settings[$key] ?? 'none');
            if ($zone === 'none' || !isset(static::ZONES[$zone])) {
                continue;
            }

            $time = Carbon::now($zone);
            $shift = (int) round(($time->utcOffset() - $own) / 60);

            $rows[] = [
                'zone' => $zone,
                'city' => static::ZONES[$zone],
                'time' => $time->format($format),
                'date' => $time->locale('ru')->isoFormat('D MMMM'),
                'shift' => $shift === 0 ? '' : ($shift > 0 ? '+' : '−') . abs($shift) . ' ч',
            ];
        }

        return ['rows' => $rows];
    }
}
