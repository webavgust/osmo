<?php

namespace App\Modules\Pub\Desktop\Widgets\Admin;

use App\Modules\Pub\AuthAttempt\Models\AuthAttempt;
use App\Modules\Pub\Desktop\Services\DesktopContext;
use App\Modules\Pub\Desktop\Widgets\Widget;
use App\Modules\Pub\User\Models\User;
use Carbon\Carbon;

/**
 * Неудачные входы (patch v30) — последние неудачные попытки авторизации.
 *
 * Источник — журнал входов user_auth_attempts (AuthAttempt, пишет
 * AuthAttemptService::failed()): success = 0, дата в attempted_at. Неудачная
 * попытка пишется без user_id (пользователь не опознан), поэтому логин
 * сопоставляется с users.login и users.email — так же, как журнал на карточке
 * пользователя (AdminUserService::attempts()). Совпал — строка ведёт на карточку.
 *
 * Счётчик считается за окно настройки (сутки или 7 дней); превышение порога
 * всплеска подсвечивается красным. Только для администратора панели.
 */
class AuthFailedWidget extends Widget
{
    /** Окна счётчика: код => [подпись, часов, название в настройках] */
    public const WINDOWS = [
        'day' => ['за сутки', 24, '24 часа'],
        'week' => ['за 7 дней', 168, '7 дней'],
    ];

    public static function id(): string
    {
        return 'auth_failed';
    }

    public static function name(): string
    {
        return 'Неудачные входы';
    }

    public static function category(): string
    {
        return 'admin';
    }

    public static function description(): string
    {
        return 'Последние неудачные попытки авторизации: когда, логин и IP';
    }

    public static function icon(): string
    {
        return 'fa-user-lock';
    }

    public static function sizes(): array
    {
        return ['8x4', '4x2', '8x8'];
    }

    public static function defaultSize(): string
    {
        return '8x4';
    }

    public static function order(): int
    {
        return 200;
    }

    public static function ttl(): int
    {
        return 120;
    }

    /** Право страницы-источника: журнал входов доступен только в админ-панели */
    public static function available(User $user): bool
    {
        return $user->isPanelAdmin();
    }

    public static function fields(): array
    {
        return [
            ['key' => 'window', 'type' => 'select', 'label' => 'Период счётчика', 'default' => 'day',
                'options' => fn() => collect(self::WINDOWS)->map(fn($window) => $window[2])->all()],
            ['key' => 'threshold', 'type' => 'number', 'label' => 'Порог всплеска', 'default' => 5, 'min' => 1, 'max' => 100,
                'hint' => 'Столько попыток за период — счётчик краснеет'],
            ['key' => 'limit', 'type' => 'number', 'label' => 'Сколько попыток в списке', 'default' => 40, 'min' => 3, 'max' => 50,
                'hint' => 'Сколько строк влезет в блок — решает высота'],
        ];
    }

    public static function sourceUrl(array $settings): ?string
    {
        // отдельной страницы журнала нет: попытки видны на карточке пользователя в админ-панели
        return route('admin.users.index');
    }

    /**
     * Окно счётчика из настроек: [подпись, часов]
     *
     * @param array $settings
     * @return array
     */
    public static function window(array $settings): array
    {
        return self::WINDOWS[(string) ($settings['window'] ?? 'day')] ?? self::WINDOWS['day'];
    }

    public function sample(array $settings, DesktopContext $ctx): array
    {
        [$label] = static::window($settings);

        $logins = ['petrov', 'admin', 'sokolova@osmoview.ru', 'ivanov', 'test', 'manager@osmoview.ru'];
        $ips = ['91.204.18.7', '203.0.113.14', '178.140.6.22', '45.83.112.9', '10.0.12.41'];
        $agents = ['Chrome, Windows', 'Firefox, Linux', 'Safari, iOS', 'Edge, Windows'];

        // до 40 попыток: высокому блоку должно быть чем заполниться; режется по limit, как в data()
        $rows = [];
        for ($i = 0; $i < min(40, (int) $settings['limit']); $i++) {
            $when = now()->subMinutes(4 + $i * $i * 4);

            $rows[] = [
                'login' => $logins[$i % 6],
                'ip' => $ips[$i % 5],
                'when' => $when->format('d.m.Y H:i'),
                'ago' => $when->diffForHumans(),
                'agent' => $agents[$i % 4],
                'user_id' => null,
                'url' => null,
            ];
        }

        return [
            'count' => 12, 'threshold' => (int) $settings['threshold'], 'alert' => 12 >= (int) $settings['threshold'],
            'logins' => 6, 'ips' => 5, 'label' => $label, 'rows' => $rows,
        ];
    }

    /**
     * Счётчик за период и последние неудачные попытки
     *
     * @param array $settings
     * @param DesktopContext $ctx
     * @return array ['count', 'threshold', 'alert', 'logins', 'ips', 'label', 'rows' => [['login', 'ip', 'when', 'ago', 'agent', 'user_id', 'url']]]
     */
    public function data(array $settings, DesktopContext $ctx): array
    {
        [$label, $hours] = static::window($settings);
        $since = now()->subHours($hours);
        $threshold = (int) $settings['threshold'];

        $window = AuthAttempt::query()->where('success', 0)->where('attempted_at', '>=', $since);
        $count = (int) (clone $window)->count();
        $logins = (int) (clone $window)->distinct()->count('login');
        $ips = (int) (clone $window)->distinct()->count('ip');

        $last = AuthAttempt::query()
            ->where('success', 0)
            ->orderByDesc('attempted_at')
            ->orderByDesc('id')
            ->limit((int) $settings['limit'])
            ->get();

        $users = static::usersByLogin($last->pluck('login')->all());

        $rows = $last->map(function (AuthAttempt $attempt) use ($users) {
            $when = $attempt->attempted_at ? Carbon::parse($attempt->attempted_at) : null;
            $user_id = $users[mb_strtolower(trim((string) $attempt->login))] ?? null;

            return [
                'login' => (string) ($attempt->login ?: '—'),
                'ip' => (string) ($attempt->ip ?: '—'),
                'when' => $when?->format('d.m.Y H:i'),
                'ago' => $when?->diffForHumans(),
                'agent' => static::agentLabel($attempt->user_agent),
                'user_id' => $user_id,
                'url' => $user_id ? route('admin.users.show', $user_id) : null,
            ];
        })->all();

        return [
            'count' => $count,
            'threshold' => $threshold,
            'alert' => $count >= $threshold,
            'logins' => $logins,
            'ips' => $ips,
            'label' => $label,
            'rows' => $rows,
        ];
    }

    /**
     * Браузер и система коротко: из user_agent строки «Chrome, Windows».
     * Не опознали — первые слова строки как есть
     *
     * @param string|null $agent
     * @return string
     */
    protected static function agentLabel(?string $agent): string
    {
        $agent = trim((string) $agent);
        if ($agent === '') return '';

        $browsers = ['Edg' => 'Edge', 'YaBrowser' => 'Яндекс', 'OPR' => 'Opera', 'Firefox' => 'Firefox', 'Chrome' => 'Chrome', 'Safari' => 'Safari'];
        $systems = ['Windows' => 'Windows', 'Android' => 'Android', 'iPhone' => 'iOS', 'iPad' => 'iOS', 'Mac OS' => 'macOS', 'Linux' => 'Linux'];

        $parts = [];
        foreach ($browsers as $needle => $label) {
            if (str_contains($agent, $needle)) { $parts[] = $label; break; }
        }
        foreach ($systems as $needle => $label) {
            if (str_contains($agent, $needle)) { $parts[] = $label; break; }
        }

        return empty($parts) ? mb_substr($agent, 0, 40) : implode(', ', $parts);
    }

    /**
     * Логины попыток, за которыми стоит существующий пользователь: логин => id
     *
     * @param array $logins
     * @return array
     */
    protected static function usersByLogin(array $logins): array
    {
        $logins = collect($logins)
            ->map(fn($login) => mb_strtolower(trim((string) $login)))
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($logins)) {
            return [];
        }

        $found = [];

        User::query()
            ->withTrashed()
            ->where(fn($query) => $query->whereIn('login', $logins)->orWhereIn('email', $logins))
            ->get(['id', 'login', 'email'])
            ->each(function (User $user) use (&$found) {
                foreach ([$user->login, $user->email] as $value) {
                    $value = mb_strtolower(trim((string) $value));
                    if ($value !== '') $found[$value] = (int) $user->id;
                }
            });

        return $found;
    }
}
