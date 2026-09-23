<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use Illuminate\Support\Facades\Route;

/**
 * Набор ссылок (patch v30): панель закладок на объекты портала — до 12 штук
 * плитками или списком.
 *
 * Разбор объекта переиспользуется у «Быстрой ссылки»: для КП, партнёра, компании
 * и сделки Битрикс24 строка собирается вызовом LinkWidget::data() — название,
 * вторая строка (статус и сумма, грейд, стадия), иконка и адрес карточки там уже
 * посчитаны, и переименование объекта подхватывается само. Страница портала
 * (имя маршрута) и внешний адрес разбираются здесь.
 *
 * Ссылка задаётся строкой «тип:значение» с необязательной подписью после «|»:
 * proposal:<group КП>, partner:<id>, company:<id>, deal:<id>, page:<имя маршрута>,
 * url:https://… (или просто адрес, начинающийся с http:// или /).
 */
class LinksWidget extends Widget
{
    /** Сколько ссылок помещается в набор */
    public const MAX = 12;

    /** Типы ссылок, которые разбирает «Быстрая ссылка» */
    public const ENTITIES = ['proposal', 'partner', 'company', 'deal'];

    public static function id(): string { return 'links'; }

    public static function name(): string { return 'Набор ссылок'; }

    public static function category(): string { return 'common'; }

    public static function description(): string
    {
        return 'Панель закладок: до 12 ссылок на КП, партнёров, компании, сделки и страницы';
    }

    public static function icon(): string { return 'fa-bookmark'; }

    public static function sizes(): array { return ['8x4', '4x4', '8x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 310; }

    public static function ttl(): int { return 300; }

    public static function fields(): array
    {
        return [
            ['key' => 'items', 'type' => 'list', 'label' => 'Ссылки (до ' . self::MAX . ')', 'default' => [],
                'hint' => 'Строка на ссылку: «тип:значение», при желании «|подпись». Типы: proposal (group КП), '
                    . 'partner, company, deal (id), page (имя маршрута портала), url (адрес http(s):// или /путь)'],
            ['key' => 'layout', 'type' => 'select', 'label' => 'Раскладка', 'default' => 'tiles',
                'options' => ['tiles' => 'Плитки', 'list' => 'Список']],
            ['key' => 'label_field', 'type' => 'select', 'label' => 'Название КП', 'default' => 'number_name',
                'hint' => 'У партнёра, компании и сделки — всегда название',
                'options' => ['number_name' => 'Номер и название', 'number' => 'Номер', 'name' => 'Название']],
            ['key' => 'second', 'type' => 'bool', 'label' => 'Вторая строка (статус и сумма, грейд, стадия)', 'default' => true],
        ];
    }

    /**
     * Ссылки набора
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [['found', 'type', 'title', 'second', 'icon', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $link = new LinkWidget();
        $rows = [];

        foreach (array_slice((array) ($settings['items'] ?? []), 0, self::MAX) as $item) {
            if (!is_scalar($item)) continue;

            $parsed = static::parse((string) $item);
            if ($parsed === null) continue;

            $rows[] = match ($parsed['type']) {
                'url' => static::external($parsed),
                'page' => static::page($parsed),
                default => static::entity($link, $parsed, $settings, $ctx),
            };
        }

        return ['rows' => $rows];
    }

    /**
     * Образцовые данные для превью
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    public function sample(array $settings, DesktopContext $ctx): array
    {
        $rows = [
            ['proposal', 'КП № AA-794 · Платформа Восток', 'Выиграно · 3,1 млн ₽', 'fa-file-invoice'],
            ['partner', 'ГК Восток', 'Gold', 'fa-handshake-simple'],
            ['company', 'ООО «Альфа»', 'ГК Восток', 'fa-building'],
            ['deal', 'Поставка лицензий, 2 кв.', 'Согласование', 'fa-handshake'],
            ['page', 'Реестр лицензий', 'Страница портала', 'fa-browser'],
            ['url', 'Регламент продаж', 'docs.example.com', 'fa-arrow-up-right-from-square'],
            // полный набор (12): высокие и широкие блоки заполняются как у пользователя с закладками
            ['proposal', 'КП № AB-112 · Облако Север', 'В работе · 840 тыс. ₽', 'fa-file-invoice'],
            ['partner', 'Интегратор Плюс', 'Silver', 'fa-handshake-simple'],
            ['company', 'АО «Бета Логистик»', 'Интегратор Плюс', 'fa-building'],
            ['deal', 'Продление подписки', 'Счёт выставлен', 'fa-handshake'],
            ['page', 'Календарь оплат', 'Страница портала', 'fa-browser'],
            ['url', 'Прайс-лист 2026', 'drive.example.com', 'fa-arrow-up-right-from-square'],
        ];

        return ['rows' => array_map(fn($row) => [
            'found' => true, 'type' => $row[0], 'title' => $row[1], 'second' => $row[2], 'icon' => $row[3], 'url' => null,
        ], $rows)];
    }

    /**
     * Разбор строки настройки: «тип:значение|подпись»
     *
     * @param string $item
     * @return array|null ['type', 'id', 'caption'] или null, если строка пустая или тип неизвестен
     */
    protected static function parse(string $item): ?array
    {
        [$value, $caption] = array_pad(array_map('trim', explode('|', trim($item), 2)), 2, '');

        if ($value === '') return null;

        $types = implode('|', array_merge(self::ENTITIES, ['page', 'url']));

        // внешний адрес пишется и без приставки: двоеточие в нём своё (https://…)
        if (preg_match('~^(' . $types . ')\s*:\s*(.+)$~ui', $value, $match)) {
            $type = mb_strtolower($match[1]);
            $id = trim($match[2]);
        } elseif (preg_match('~^(https?://|/)~i', $value)) {
            $type = 'url';
            $id = $value;
        } else {
            return null;
        }

        // адрес пускаем только http(s) и относительный, чтобы в href не попал javascript:
        if ($type === 'url' && !preg_match('~^(https?://|/)~i', $id)) return null;

        return $id === '' ? null : ['type' => $type, 'id' => $id, 'caption' => $caption];
    }

    /**
     * Объект портала: название, вторая строка и адрес карточки считает «Быстрая ссылка»
     *
     * @param LinkWidget $link
     * @param array $parsed
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array
     */
    protected static function entity(LinkWidget $link, array $parsed, array $settings, DesktopContext $ctx): array
    {
        $found = $link->data([
            'target' => ['type' => $parsed['type'], 'id' => $parsed['id']],
            'label_field' => (string) ($settings['label_field'] ?? 'number_name'),
            'second_line' => !empty($settings['second']) ? 'auto' : 'none',
        ], $ctx);

        $title = $parsed['caption'] !== ''
            ? $parsed['caption']
            : ($found['found'] ? (string) $found['title'] : 'Не найден: ' . $parsed['id']);

        return [
            'found' => (bool) $found['found'],
            'type' => $parsed['type'],
            'title' => $title,
            'second' => (string) $found['second'],
            'icon' => (string) $found['icon'],
            'url' => $found['url'],
        ];
    }

    /**
     * Страница портала по имени маршрута
     *
     * @param array $parsed
     * @return array
     */
    protected static function page(array $parsed): array
    {
        // страница — только GET-маршрут (POST-маршрут по ссылке ответит 405); маршрут может
        // требовать параметров — тогда адрес не собирается, и ссылка мёртвая
        $route = Route::getRoutes()->getByName($parsed['id']);
        try {
            $url = $route && in_array('GET', $route->methods(), true) ? route($parsed['id']) : null;
        } catch (\Throwable $e) {
            $url = null;
        }

        return [
            'found' => $url !== null,
            'type' => 'page',
            'title' => $parsed['caption'] !== '' ? $parsed['caption'] : $parsed['id'],
            'second' => $url !== null ? 'Страница портала' : ($route ? 'Маршрут не открывается ссылкой' : 'Маршрут не найден'),
            'icon' => 'fa-browser',
            'url' => $url,
        ];
    }

    /**
     * Внешний адрес: во второй строке — домен
     *
     * @param array $parsed
     * @return array
     */
    protected static function external(array $parsed): array
    {
        $host = (string) (parse_url($parsed['id'], PHP_URL_HOST) ?: '');

        return [
            'found' => true,
            'type' => 'url',
            'title' => $parsed['caption'] !== '' ? $parsed['caption'] : ($host !== '' ? $host : $parsed['id']),
            'second' => $host !== '' ? $host : 'Ссылка портала',
            'icon' => 'fa-arrow-up-right-from-square',
            'url' => $parsed['id'],
        ];
    }
}
