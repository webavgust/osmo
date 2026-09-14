<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Заголовок ряда (patch v30): подпись раздела стола с иконкой и линией.
 *
 * Прозрачный блок без рамки высотой в одну ячейку; данных нет, всё из настроек.
 */
class HeadingWidget extends Widget
{
    /** Иконки заголовка */
    public const ICONS = [
        'none' => 'Без иконки',
        'fa-chart-line' => 'График',
        'fa-money-bill-wave' => 'Деньги',
        'fa-file-invoice' => 'КП',
        'fa-key' => 'Ключ',
        'fa-handshake-simple' => 'Партнёры',
        'fa-filter' => 'Воронка',
        'fa-star' => 'Звезда',
        'fa-bell' => 'Колокольчик',
    ];

    /** Цвет текста и иконки (цвета Metronic) */
    public const COLORS = [
        'gray-900' => 'Тёмный',
        'primary' => 'Синий',
        'info' => 'Фиолетовый',
        'success' => 'Зелёный',
        'warning' => 'Жёлтый',
        'danger' => 'Красный',
    ];

    public static function id(): string
    {
        return 'heading';
    }

    public static function name(): string
    {
        return 'Заголовок ряда';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Подпись раздела стола: делит виджеты на группы «Финансы», «Воронка», «Ключи»';
    }

    public static function icon(): string
    {
        return 'fa-heading';
    }

    public static function sizes(): array
    {
        return ['32x1', '16x1', '8x1'];
    }

    public static function defaultSize(): string
    {
        return '32x1';
    }

    public static function order(): int
    {
        return 910;
    }

    public static function showTitle(): bool
    {
        return false;
    }

    public static function framed(): bool
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
            ['key' => 'text', 'type' => 'text', 'label' => 'Текст', 'default' => 'Раздел', 'required' => true, 'max' => 80],
            ['key' => 'icon', 'type' => 'select', 'label' => 'Иконка', 'default' => 'none', 'options' => static::ICONS],
            ['key' => 'color', 'type' => 'select', 'label' => 'Цвет', 'default' => 'gray-900', 'options' => static::COLORS],
            ['key' => 'line', 'type' => 'bool', 'label' => 'Линия после текста', 'default' => true],
        ];
    }

    /**
     * Настройки для превью в библиотеке
     *
     * @return array
     */
    public static function previewSettings(): array
    {
        return ['text' => 'Финансы', 'icon' => 'fa-money-bill-wave'];
    }

    /**
     * Данных нет: всё берётся из настроек
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        return [];
    }
}
