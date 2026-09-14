<?php

namespace App\Modules\Pub\Desktop\Widgets\Common;

use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;

/**
 * Кто в сети (patch v30): коллеги, недавно заходившие на портал.
 *
 * Отбор — как в списке пользователей: без скрытых, при желании только активные;
 * порядок по последнему визиту (users.last_hit_at). Онлайн — визит меньше
 * ONLINE_MINUTES минут назад, ровно как свойство User::is_online.
 */
class TeamWidget extends Widget
{
    /** Сколько минут после визита считается «в сети» (User::getIsOnlineAttribute) */
    public const ONLINE_MINUTES = 10;

    public static function id(): string { return 'team'; }

    public static function name(): string { return 'Кто в сети'; }

    public static function category(): string { return 'common'; }

    public static function description(): string
    {
        return 'Коллеги, заходившие на портал недавно: кто сейчас в сети и когда был последний визит';
    }

    public static function icon(): string { return 'fa-users'; }

    public static function sizes(): array { return ['8x2', '4x2', '8x4']; }

    public static function defaultSize(): string { return '8x2'; }

    public static function order(): int { return 320; }

    /** Виджет обновляется часто: визит пишется на каждой странице */
    public static function ttl(): int { return 60; }

    public static function fields(): array
    {
        return [
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько человек', 'default' => 12, 'min' => 3, 'max' => 40],
            ['key' => 'only_active', 'type' => 'bool', 'label' => 'Только активные пользователи', 'default' => true],
            ['key' => 'only_online', 'type' => 'bool', 'label' => 'Только те, кто в сети', 'default' => false],
            ['key' => 'show_position', 'type' => 'bool', 'label' => 'Показывать должность', 'default' => true],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('users.list');
    }

    /**
     * Недавние визиты
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['online', 'total', 'rows' => [['id', 'name', 'position', 'phone', 'avatar', 'online', 'when', 'ago', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $query = User::query()
            ->whereNot('is_hidden', 1)
            ->whereNotNull('last_hit_at');

        if (!empty($settings['only_active'])) {
            $query->where('active', 1);
        }

        $edge = now()->subMinutes(static::ONLINE_MINUTES);

        if (!empty($settings['only_online'])) {
            $query->where('last_hit_at', '>=', $edge);
        }

        $users = $query->orderByDesc('last_hit_at')->limit((int) $settings['limit'])->get();

        $rows = $users->map(fn(User $user) => [
            'id' => (int) $user->id,
            'name' => trim((string) ($user->full_name ?: $user->name)),
            'position' => trim((string) $user->work_position),
            'phone' => trim((string) ($user->work_phone ?: $user->personal_mobile)),
            'avatar' => (string) asset($user->avatar()),
            'online' => (bool) $user->is_online,
            'when' => $user->last_hit_at->format('d.m.Y H:i'),
            'ago' => $user->last_hit_at->diffForHumans(),
            'url' => route('users.view', $user->id),
        ])->all();

        return [
            'online' => count(array_filter($rows, fn($row) => $row['online'])),
            'total' => count($rows),
            'rows' => $rows,
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
        // 40 человек (максимум настройки «Сколько человек»): высоким блокам есть чем заполниться
        $names = [
            'Анна Соколова', 'Алексей Гордеев', 'Наталья Ким', 'Роман Сергеев', 'Ольга Белова',
            'Дмитрий Орлов', 'Екатерина Лебедева', 'Игорь Фомин', 'Мария Кузнецова', 'Сергей Тихонов',
            'Юлия Морозова', 'Павел Новиков', 'Ирина Волкова', 'Андрей Зайцев', 'Светлана Павлова',
            'Максим Егоров', 'Татьяна Семёнова', 'Константин Голубев', 'Елена Виноградова', 'Артём Богданов',
        ];
        $positions = [
            'Руководитель отдела продаж', 'Менеджер по работе с партнёрами', 'Экономист', 'Технический директор',
            'Бухгалтер', 'Инженер внедрения', 'Юрист', 'Менеджер проектов',
        ];

        $sample = [];
        for ($i = 0; $i < 40; $i++) {
            // первые трое в сети, дальше визиты всё раньше: от минут до дней
            $minutes = $i < 3 ? $i * 3 : (int) round(12 * pow(1.28, $i));
            $sample[] = [
                $names[$i % 20] . ($i >= 20 ? '-' . ($i - 19) : ''),
                $positions[$i % 8],
                $minutes,
                $i % 3 === 0 ? '+7 (495) 120-' . str_pad((string) (10 + $i), 2, '0', STR_PAD_LEFT) . '-' . str_pad((string) (40 + $i), 2, '0', STR_PAD_LEFT) : '',
            ];
        }

        $rows = array_map(function ($row) {
            $when = now()->subMinutes($row[2]);

            return [
                'id' => 0,
                'name' => $row[0],
                'position' => $row[1],
                'phone' => $row[3],
                'avatar' => (string) asset(config('settings.user_avatar_default')),
                'online' => $row[2] < static::ONLINE_MINUTES,
                'when' => $when->format('d.m.Y H:i'),
                'ago' => $when->diffForHumans(),
                'url' => null,
            ];
        }, $sample);

        return [
            'online' => count(array_filter($rows, fn($row) => $row['online'])),
            'total' => count($rows),
            'rows' => $rows,
        ];
    }
}
