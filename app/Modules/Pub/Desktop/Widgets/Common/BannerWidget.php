<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Str;

/**
 * Баннер во всю ширину (patch v30): заголовок, текст и кнопка-ссылка.
 *
 * Образец простого виджета без данных: всё берётся из настроек.
 */
class BannerWidget extends Widget
{
    /** Фон баннера */
    public const STYLES = [
        'gradient' => 'Градиент',
        'primary' => 'Синий',
        'info' => 'Фиолетовый',
        'success' => 'Зелёный',
        'warning' => 'Жёлтый',
        'danger' => 'Красный',
        'dark' => 'Тёмный',
        'light' => 'Светлый',
    ];

    public static function id(): string
    {
        return 'banner';
    }

    public static function name(): string
    {
        return 'Баннер';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Заголовок, текст и кнопка на всю ширину стола: объявление, план квартала, подсказка';
    }

    public static function icon(): string
    {
        return 'fa-rectangle-wide';
    }

    public static function sizes(): array
    {
        return ['32x2', '32x4', '32x6'];
    }

    public static function defaultSize(): string
    {
        return '32x4';
    }

    public static function order(): int
    {
        return 900;
    }

    public static function showTitle(): bool
    {
        return false;
    }

    public static function ttl(): int
    {
        return 0;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'headline', 'type' => 'text', 'label' => 'Заголовок баннера', 'default' => 'Заголовок баннера', 'max' => 120, 'required' => true],
            ['key' => 'text', 'type' => 'textarea', 'label' => 'Текст', 'default' => '', 'max' => 300, 'hint' => 'При высоте 2 ячейки не показывается'],
            ['key' => 'style', 'type' => 'select', 'label' => 'Фон', 'default' => 'gradient', 'options' => static::STYLES],
            ['key' => 'align', 'type' => 'select', 'label' => 'Выравнивание', 'default' => 'left', 'options' => ['left' => 'Слева', 'center' => 'По центру']],
            ['key' => 'button_label', 'type' => 'text', 'label' => 'Кнопка: подпись', 'default' => '', 'max' => 40],
            ['key' => 'button_url', 'type' => 'text', 'label' => 'Кнопка: ссылка', 'default' => '', 'max' => 500, 'hint' => 'Адрес портала (/proposals) или полный https://…'],
        ];
    }

    /**
     * Настройки для превью в библиотеке
     *
     * @return array
     */
    public static function previewSettings(): array
    {
        return [
            'headline' => 'План III квартала: 60 млн ₽',
            'text' => 'До конца квартала 16 дней — проверьте КП в работе',
            'button_label' => 'Открыть воронку',
            'button_url' => '/bitrix/dashboard',
        ];
    }

    public function data(array $settings, DesktopContext $ctx): array
    {
        $url = trim((string) $settings['button_url']);
        $safe = Str::startsWith($url, ['/', 'https://', 'http://']) && !Str::startsWith($url, '//');

        return ['url' => $safe ? $url : null];
    }
}
