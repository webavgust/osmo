<?php

namespace App\Modules\Pub\Desktop\Widgets\Admin;

use App\Modules\Admin\Users\Services\AdminUserService;
use App\Modules\Pub\AuthAttempt\Models\AuthAttempt;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;

/**
 * Пользователи: активность (patch v30) — кто сейчас на портале и когда заходил
 * в последний раз.
 *
 * Список берётся так же, как таблица админ-панели (AdminUserService::list()):
 * активные пользователи, без мягко удалённых. «В сети» — то же правило, что и у
 * User::is_online: обращение к порталу не позже чем ONLINE_SECONDS назад
 * (users.last_hit_at). Последний успешный вход — AdminUserService::lastLogins()
 * (user_auth_attempts.success = 1). Счётчик неудачных попыток за сутки —
 * user_auth_attempts.success = 0, он же показан в виджете «Неудачные входы».
 *
 * Только для администратора панели: страница-источник — /admin/users.
 */
class UsersOnlineWidget extends Widget
{
    /** Сколько секунд после обращения пользователь считается «в сети» (как User::is_online) */
    public const ONLINE_SECONDS = 600;

    public static function id(): string
    {
        return 'users_online';
    }

    public static function name(): string
    {
        return 'Пользователи: активность';
    }

    public static function category(): string
    {
        return 'admin';
    }

    public static function description(): string
    {
        return 'Кто сейчас на портале и когда заходил последний раз';
    }

    public static function icon(): string
    {
        return 'fa-users';
    }

    public static function sizes(): array
    {
        return ['8x4', '8x8', '16x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 100;
    }

    /** Активность меняется часто — короткий кэш */
    public static function ttl(): int
    {
        return 60;
    }

    /** Право страницы-источника: пользователями заведует только админ-панель */
    public static function available(User $user): bool
    {
        return $user->isPanelAdmin();
    }

    public static function fields(): array
    {
        return [
            ['key' => 'failed', 'type' => 'bool', 'label' => 'Неудачные попытки за сутки', 'default' => true],
            ['key' => 'only_online', 'type' => 'bool', 'label' => 'Только те, кто в сети', 'default' => false],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько пользователей', 'default' => 40, 'min' => 3, 'max' => 50,
                'hint' => 'Сколько строк влезет в блок — решает высота'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        return route('admin.users.index', ['state' => 'active']);
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        $base = [
            ['Соколова Анна Ивановна', 'Руководитель отдела продаж'],
            ['Гордеев Алексей Петрович', 'Менеджер'],
            ['Ким Ольга Сергеевна', 'Технический специалист'],
            ['Новиков Сергей Львович', 'Менеджер'],
            ['Белова Ирина Павловна', 'Бухгалтер'],
            ['Орлов Дмитрий Андреевич', 'Руководитель направления внедрения'],
            ['Лебедева Мария Олеговна', 'Аналитик'],
            ['Захаров Игорь Викторович', 'Инженер поддержки'],
        ];

        // до 40 пользователей: высокому блоку должно быть чем заполниться; режется по limit, как в data()
        $sample = [];
        for ($i = 0; $i < min(40, (int) $settings['limit']); $i++) {
            [$name, $position] = $base[$i % 8];
            $hit = $i < 2 ? $i * 3 : $i * $i * 25;
            $sample[] = [$name . ($i >= 8 ? ' ' . (intdiv($i, 8) + 1) : ''), $position, $hit, $hit + 15];
        }

        $rows = [];
        foreach ($sample as $i => [$name, $position, $hit_minutes, $login_minutes]) {
            $hit = now()->subMinutes($hit_minutes);
            $login = now()->subMinutes($login_minutes);

            $rows[] = [
                'id' => $i + 1,
                'name' => $name,
                'position' => $position,
                'online' => $hit_minutes * 60 <= self::ONLINE_SECONDS,
                'hit' => $hit->format('d.m.Y H:i'),
                'ago' => $hit->diffForHumans(),
                'login' => $login->format('d.m.Y H:i'),
                'url' => null,
            ];
        }

        return ['online' => min(2, count($rows)), 'total' => 46, 'failed' => 3, 'rows' => $rows];
    }

    /**
     * Пользователи по свежести последнего обращения к порталу
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['online', 'total', 'failed', 'rows' => [['id', 'name', 'position', 'online', 'hit', 'ago', 'login', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        $edge = now()->subSeconds(self::ONLINE_SECONDS);
        $users = AdminUserService::list(['q' => '', 'state' => 'active']);

        $logins = AdminUserService::lastLogins($users->pluck('id')->all());

        // сначала те, кто в сети, дальше — по свежести последнего обращения
        $sorted = $users->sortByDesc(fn(User $user) => $user->last_hit_at?->getTimestamp() ?? 0)->values();

        if (!empty($settings['only_online'])) {
            $sorted = $sorted->filter(fn(User $user) => $user->last_hit_at && $user->last_hit_at->greaterThanOrEqualTo($edge))->values();
        }

        $rows = $sorted->take((int) $settings['limit'])->map(function (User $user) use ($edge, $logins) {
            $login = $logins[$user->id] ?? null;

            return [
                'id' => (int) $user->id,
                'name' => (string) ($user->full_name ?: $user->name),
                'position' => (string) ($user->work_position ?: ''),
                'online' => (bool) ($user->last_hit_at && $user->last_hit_at->greaterThanOrEqualTo($edge)),
                'hit' => $user->last_hit_at?->format('d.m.Y H:i'),
                'ago' => $user->last_hit_at?->diffForHumans(),
                'login' => $login ? Carbon::parse($login)->format('d.m.Y H:i') : null,
                'url' => route('admin.users.show', $user->id),
            ];
        })->all();

        return [
            'online' => $users->filter(fn(User $user) => $user->last_hit_at && $user->last_hit_at->greaterThanOrEqualTo($edge))->count(),
            'total' => $users->count(),
            'failed' => (int) AuthAttempt::query()->where('success', 0)->where('attempted_at', '>=', now()->subDay())->count(),
            'rows' => $rows,
        ];
    }
}
