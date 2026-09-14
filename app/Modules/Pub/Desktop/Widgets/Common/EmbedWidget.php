<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Constant\Models\Constant;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;

/**
 * Внешняя страница (patch v30): чужой отчёт прямо на столе — публичный дашборд
 * Битрикс24, отчёт Метрики и подобное.
 *
 * Адрес пускается только с домена из белого списка: константа портала
 * `desktop_embed_domains` (домены через запятую или с новой строки, поддомены
 * считаются своими). Список пуст — виджет ничего не открывает и говорит об этом.
 */
class EmbedWidget extends Widget
{
    /** Константа портала с белым списком доменов */
    public const DOMAINS_KEY = 'desktop_embed_domains';

    public static function id(): string
    {
        return 'embed';
    }

    public static function name(): string
    {
        return 'Внешняя страница';
    }

    public static function category(): string
    {
        return 'common';
    }

    public static function description(): string
    {
        return 'Встроенный внешний отчёт с разрешённого домена';
    }

    public static function icon(): string
    {
        return 'fa-browser';
    }

    public static function sizes(): array
    {
        return ['32x12', '16x8', '8x8', '32x8'];
    }

    public static function defaultSize(): string
    {
        return '32x12';
    }

    public static function order(): int
    {
        return 200;
    }

    public static function framed(): bool
    {
        return true;
    }

    public static function ttl(): int
    {
        return 0;
    }

    public static function fields(): array
    {
        return [
            ['key' => 'url', 'type' => 'text', 'label' => 'Адрес страницы', 'required' => true, 'default' => '',
                'hint' => 'Только домены из константы ' . self::DOMAINS_KEY],
            ['key' => 'reload', 'type' => 'number', 'label' => 'Обновлять каждые N минут', 'default' => 0, 'min' => 0, 'max' => 240,
                'hint' => '0 — не обновлять'],
        ];
    }

    public static function available(User $user): bool
    {
        return true;
    }

    public static function sourceUrl(array $settings): ?string
    {
        $url = trim((string) ($settings['url'] ?? ''));

        return static::allowed($url) ? $url : null;
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        return ['url' => null, 'allowed' => false, 'host' => 'example.com', 'domains' => static::domains(), 'reload' => 0, 'preview' => true];
    }

    /**
     * Адрес страницы, если он разрешён белым списком
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['url', 'allowed', 'host', 'domains', 'reload', 'preview']
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $url = trim((string) $settings['url']);
        $host = $url !== '' ? (string) parse_url($url, PHP_URL_HOST) : '';

        return [
            'url' => $url,
            'allowed' => static::allowed($url),
            'host' => $host,
            'domains' => static::domains(),
            // в секундах: столом самообновлением занимается tickReload в osmo-desktop.js
            'reload' => max(0, (int) $settings['reload']) * 60,
            'preview' => false,
        ];
    }

    /**
     * Белый список доменов из константы портала
     *
     * @return array
     */
    public static function domains(): array
    {
        $value = (string) Constant::value(static::DOMAINS_KEY, '');

        return collect(preg_split('~[\s,;]+~', $value))
            ->map(fn($domain) => strtolower(trim((string) $domain, " \t\n\r\0\x0B/")))
            ->filter(fn($domain) => $domain !== '')
            ->values()
            ->all();
    }

    /**
     * Адрес ведёт на разрешённый домен (поддомены считаются своими)
     *
     * @param string $url
     * @return bool
     */
    public static function allowed(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if ($host === '' || !in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        foreach (static::domains() as $domain) {
            if ($host === $domain || str_ends_with($host, '.' . $domain)) {
                return true;
            }
        }

        return false;
    }
}
