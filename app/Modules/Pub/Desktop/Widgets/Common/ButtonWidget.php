<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Действие (patch v30): одна кнопка — один частый переход или попап портала.
 *
 * Действия перечислены белым списком: страница создания, попап или сайдбар,
 * которые уже есть в портале. Недоступные пользователю по правам или выключенные
 * модулем маршруты в список настроек не попадают — кнопка не может увести туда,
 * куда сам портал не пускает.
 */
class ButtonWidget extends Widget
{
    /**
     * Белый список действий: ключ => [подпись, маршрут, как открывать, иконка].
     *
     * Как открывать: page — переход, box — попап box({href}), sidebar — боковая панель
     */
    public const ACTIONS = [
        'proposal_create' => ['Создать КП', 'proposal.create', 'page', 'fa-file-circle-plus'],
        'company_create' => ['Новая компания', 'company.create', 'page', 'fa-building-circle-arrow-right'],
        'partner_create' => ['Новый партнёр', 'partner.create', 'page', 'fa-handshake'],
        'note_add' => ['Заметка в блокнот', 'user-notes.sidebar_add', 'sidebar', 'fa-note-sticky'],
        'deals_export' => ['Выгрузить реестр сделок', 'crm-deal.box.export', 'box', 'fa-file-excel'],
        'deals_registry' => ['Реестр сделок Битрикс24', 'crm-deal.index', 'page', 'fa-table-list'],
        'payment_calendar' => ['Платёжный календарь', 'payment_calendar.index', 'page', 'fa-calendar-days'],
        'custom' => ['Свой адрес', '', 'page', 'fa-arrow-up-right-from-square'],
    ];

    public static function id(): string
    {
        return 'button';
    }

    public static function name(): string
    {
        return 'Действие';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Кнопка на частое действие: создать КП, открыть реестр, добавить заметку';
    }

    public static function icon(): string
    {
        return 'fa-bolt';
    }

    public static function sizes(): array
    {
        return ['4x2', '2x2', '8x2'];
    }

    public static function defaultSize(): string
    {
        return '4x2';
    }

    public static function order(): int
    {
        return 50;
    }

    public static function ttl(): int
    {
        return 0;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'action', 'type' => 'select', 'label' => 'Действие', 'required' => true, 'default' => 'proposal_create',
                'options' => fn() => collect(static::actions())->map(fn($action) => $action[0])->all()],
            ['key' => 'url', 'type' => 'text', 'label' => 'Адрес', 'default' => '', 'hint' => 'Для действия «Свой адрес»'],
            ['key' => 'label', 'type' => 'text', 'label' => 'Подпись', 'default' => '', 'hint' => 'Пусто — название действия'],
            ['key' => 'color', 'type' => 'select', 'label' => 'Цвет кнопки', 'default' => 'primary',
                'options' => ['primary' => 'Синяя', 'light-primary' => 'Светло-синяя', 'success' => 'Зелёная',
                    'warning' => 'Жёлтая', 'danger' => 'Красная', 'light' => 'Серая', 'dark' => 'Тёмная']],
            ['key' => 'confirm', 'type' => 'bool', 'label' => 'Спрашивать подтверждение', 'default' => false],
        ];
    }

    public static function available(User $user): bool
    {
        return !empty(static::actions());
    }

    public static function sourceUrl(array $settings): ?string
    {
        return null;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return ['label' => 'Создать КП', 'icon' => 'fa-file-circle-plus', 'url' => '#', 'mode' => 'page', 'color' => 'primary', 'confirm' => false];
    }

    /**
     * Куда ведёт кнопка и как её открывать
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['label', 'icon', 'url', 'mode', 'color', 'confirm']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $key = (string) $settings['action'];
        $actions = static::actions();
        $action = $actions[$key] ?? null;

        $out = [
            'label' => trim((string) $settings['label']),
            'icon' => $action[3] ?? static::icon(),
            'url' => null,
            'mode' => $action[2] ?? 'page',
            'color' => (string) $settings['color'],
            'confirm' => (bool) $settings['confirm'],
        ];

        if (!$action) {
            return $out;
        }

        if ($out['label'] === '') {
            $out['label'] = $action[0];
        }

        $out['url'] = $key === 'custom'
            ? static::externalUrl((string) $settings['url'])
            : route($action[1]);

        return $out;
    }

    /**
     * Действия, доступные текущему пользователю: маршрут объявлен и права позволяют
     *
     * @return array
     */
    public static function actions(): array
    {
        return collect(static::ACTIONS)
            ->filter(fn($action, $key) => $key === 'custom' || Route::has($action[1]))
            ->all();
    }

    /**
     * Свой адрес: только http(s) или путь внутри портала
     *
     * @param string $url
     * @return string|null
     */
    protected static function externalUrl(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https'], true) ? $url : null;
    }
}
