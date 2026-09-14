<?php

namespace App\Modules\Pub\Desktop\Widgets\Personal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;

/**
 * Заметка (patch v30): стикер с текстом из настроек.
 *
 * Корень вьюхи помечен data-desk-note — страница стола включает по нему
 * правку текста двойным кликом.
 */
class NoteWidget extends Widget
{
    /** Цвет стикера => фон Metronic */
    public const COLORS = [
        'yellow' => 'Жёлтый стикер',
        'green' => 'Зелёный',
        'blue' => 'Синий',
        'purple' => 'Фиолетовый',
        'none' => 'Без цвета',
    ];

    /** Класс фона по цвету стикера */
    public const BACKGROUNDS = [
        'yellow' => 'bg-light-warning',
        'green' => 'bg-light-success',
        'blue' => 'bg-light-primary',
        'purple' => 'bg-light-info',
        'none' => '',
    ];

    public static function id(): string
    {
        return 'note';
    }

    public static function name(): string
    {
        return 'Заметка';
    }

    public static function category(): string
    {
        return 'personal';
    }

    public static function description(): string
    {
        return 'Стикер с текстом: план на день, телефон, напоминание себе';
    }

    public static function icon(): string
    {
        return 'fa-note-sticky';
    }

    public static function sizes(): array
    {
        return ['4x4', '4x2', '8x4', '8x8'];
    }

    public static function defaultSize(): string
    {
        return '4x4';
    }

    public static function order(): int
    {
        return 100;
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
            ['key' => 'text', 'type' => 'textarea', 'label' => 'Текст', 'default' => '', 'max' => 2000, 'hint' => 'Переносы строк сохраняются'],
            ['key' => 'color', 'type' => 'select', 'label' => 'Цвет', 'default' => 'yellow', 'options' => static::COLORS],
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
            'text' => "Позвонить партнёру «Инфосистемы» до 15:00\nОбсудить скидку по КП AA-794",
            'color' => 'yellow',
        ];
    }

    /**
     * Данных нет: текст берётся из настроек
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
