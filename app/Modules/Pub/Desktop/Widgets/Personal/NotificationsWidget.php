<?php

namespace App\Modules\Pub\Desktop\Widgets\Personal;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\Notify\Models\Notify;
use Illuminate\Support\Str;

/**
 * Уведомления (patch v30): последние уведомления портала, как в шапке.
 *
 * Читается таблица notifies текущего пользователя (удалённые не берём — это же
 * делает NotifyRepository::getActual на странице истории). Непрочитанные
 * (showed = 0) выделены; виджет только показывает и ничего не помечает
 * прочитанным — этим занимается шапка портала.
 */
class NotificationsWidget extends Widget
{
    /** Иконка уведомления, когда своей нет или она непонятная */
    public const ICON = 'fa-bell';

    public static function id(): string { return 'notifications'; }

    public static function name(): string { return 'Уведомления'; }

    public static function category(): string { return 'personal'; }

    public static function description(): string
    {
        return 'Последние уведомления портала, непрочитанные выделены';
    }

    public static function icon(): string { return 'fa-bell'; }

    public static function sizes(): array { return ['8x4', '8x8', '16x8']; }

    public static function defaultSize(): string { return '8x4'; }

    public static function order(): int { return 500; }

    public static function ttl(): int { return 60; }

    public static function fields(): array
    {
        return [
            ['key' => 'unread', 'type' => 'bool', 'label' => 'Только непрочитанные', 'default' => false],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Количество', 'default' => 10, 'min' => 1, 'max' => 50],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('notify.list');
    }

    /**
     * Строки: ['id', 'title', 'message', 'icon', 'unread', 'time', 'ago', 'url']
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['rows' => [...], 'unread' => int, 'only_unread' => bool]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $user = $ctx->user;
        if (!$user) {
            return ['rows' => [], 'unread' => 0, 'only_unread' => (bool) $settings['unread']];
        }

        $query = Notify::query()->where('user_id', $user->id)->orderByDesc('id');
        if ($settings['unread']) $query->where('showed', 0);

        $notifies = $query->limit((int) $settings['limit'])->get();

        $rows = [];
        foreach ($notifies as $notify) {
            $created = $notify->created_at;
            $rows[] = [
                'id' => $notify->id,
                'title' => Str::limit(static::plain((string) $notify->title), 70),
                'message' => Str::limit(static::plain((string) $notify->message), 140),
                'icon' => static::iconOf($notify->icon),
                'unread' => !$notify->showed,
                // год — только у уведомлений не этого года
                'time' => $created ? $created->format($created->isCurrentYear() ? 'd.m H:i' : 'd.m.y') : '',
                'ago' => $created ? $created->diffForHumans() : '',
                // без своей ссылки — история уведомлений, а не мёртвая строка
                'url' => trim((string) $notify->link) !== '' ? (string) $notify->link : route('notify.list'),
            ];
        }

        return [
            'rows' => $rows,
            'unread' => Notify::query()->where('user_id', $user->id)->where('showed', 0)->count(),
            'only_unread' => (bool) $settings['unread'],
        ];
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
        // столько уведомлений, сколько разрешено настройкой (до 40): высокий блок есть чем заполнить
        $pool = [
            ['КП AA-794 выиграно', 'Партнёр «Инфосистемы» подтвердил заказ', 'fa-bell'],
            ['Ключи ООО «Восток» истекают', '12 лицензий, осталось 7 дней', 'fa-key'],
            ['Поступила оплата по договору № 118', 'Оплата 1 240 000 ₽ зачислена', 'fa-ruble-sign'],
            ['Отчёт по воронке готов', 'Выгрузка за квартал', 'fa-file'],
            ['Новая сделка в Битрикс24', 'ВКС «Русал», ответственный — вы', 'fa-handshake'],
            ['Созвон в 15:00', 'Партнёр в Пекине, разница +5 ч', 'fa-alarm-clock'],
            ['Комментарий к КП AA-801', 'Руководитель согласовал скидку 12 %', 'fa-comment'],
            ['Задача просрочена', 'Подготовить спецификацию к договору № 121', 'fa-triangle-exclamation'],
        ];

        $rows = [];
        for ($i = 0, $n = min(40, max(1, (int) $settings['limit'])); $i < $n; $i++) {
            [$title, $message, $icon] = $pool[$i % count($pool)];
            $created = now()->subHours($i * 7);
            $rows[] = [
                'id' => $i + 1, 'title' => $title, 'message' => $message, 'icon' => $icon, 'unread' => $i < 3,
                'time' => $created->format('d.m H:i'), 'ago' => $created->locale('ru')->diffForHumans(), 'url' => null,
            ];
        }

        return [
            'rows' => $settings['unread'] ? array_slice($rows, 0, 3) : $rows,
            'unread' => 3,
            'only_unread' => (bool) $settings['unread'],
        ];
    }

    /**
     * Иконка уведомления: только понятное имя Font Awesome
     *
     * @param string|null $icon
     * @return string
     */
    protected static function iconOf(?string $icon): string
    {
        $icon = trim((string) $icon);

        return preg_match('/^fa-[a-z0-9\-]+$/', $icon) ? $icon : static::ICON;
    }

    /**
     * Текст уведомления одной строкой: в title и message бывает вёрстка (шапка выводит их
     * как HTML), поэтому теги снимаются, а сущности (&laquo;, &nbsp;) раскрываются —
     * иначе Blade покажет их буквами
     *
     * @param string $text
     * @return string
     */
    protected static function plain(string $text): string
    {
        $text = strip_tags(str_replace(['<br>', '<br/>', '<br />'], ' ', $text));
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $text) ?? '');
    }
}
