<?php

namespace App\Modules\Admin\Users\Services;

use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\AuthAttempt\Models\AuthAttempt;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Управление пользователями в админ-панели (patch v28, этап B).
 *
 * Вход на портал (UserController::Auth): пользователь ищется по логину или
 * email, затем Auth::attempt(['login', 'password']) — Eloquent-провайдер
 * сверяет пароль через Hash::check, драйвер bcrypt (config/hashing.php).
 * Поэтому пароль здесь хешируется Hash::make — тем же драйвером.
 *
 * Удаление только мягкое (SoftDeletes): данные пользователя, права,
 * настройки и журнал входов остаются на месте, восстановление возвращает всё.
 */
class AdminUserService
{
    /** Права публичной части, которые выдаются новому пользователю (mode = 1) */
    public const DEFAULT_ACCESSES = ['general_access', 'payment_calendar_view', 'deal_card_view'];

    /** Отбор списка: все (без удалённых), активные, отключённые, удалённые */
    public const STATES = [
        '' => 'Все',
        'active' => 'Активные',
        'inactive' => 'Отключённые',
        'trashed' => 'Удалённые',
    ];

    /**
     * Параметры отбора списка из запроса
     *
     * @param array $input
     * @return array
     */
    public static function params(array $input): array
    {
        $state = (string) ($input['state'] ?? '');

        return [
            'q' => trim((string) ($input['q'] ?? '')),
            'state' => array_key_exists($state, self::STATES) ? $state : '',
        ];
    }

    /**
     * Список пользователей для таблицы админ-панели
     *
     * @param array $params результат params()
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function list(array $params)
    {
        $builder = User::query();

        match ($params['state']) {
            'trashed' => $builder->onlyTrashed(),
            'active' => $builder->where('active', 1),
            'inactive' => $builder->where('active', 0),
            default => null,
        };

        if ($params['q'] !== '') {
            // каждое слово должно найтись в ФИО, логине или email
            foreach (preg_split('/\s+/u', $params['q']) as $word) {
                $builder->where(function ($query) use ($word) {
                    foreach (['name', 'last_name', 'second_name', 'login', 'email'] as $field) {
                        $query->orWhere($field, 'LIKE', '%' . $word . '%');
                    }
                });
            }
        }

        return $builder
            ->orderByDesc('active')
            ->orderBy('last_name')
            ->orderBy('name')
            ->get();
    }

    /**
     * Последний успешный вход по каждому пользователю
     *
     * @param array $ids
     * @return \Illuminate\Support\Collection [user_id => 'Y-m-d H:i:s']
     */
    public static function lastLogins(array $ids)
    {
        if (empty($ids)) {
            return collect();
        }

        return AuthAttempt::query()
            ->selectRaw('user_id, MAX(attempted_at) as last_at')
            ->where('success', 1)
            ->whereIn('user_id', $ids)
            ->groupBy('user_id')
            ->pluck('last_at', 'user_id');
    }

    /**
     * Журнал авторизаций пользователя.
     *
     * Неудачные попытки пишутся без user_id, поэтому берём и записи по логину
     * или email пользователя.
     *
     * @param User $user
     * @param int $per_page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public static function attempts(User $user, int $per_page = 50)
    {
        $logins = collect([$user->login, $user->email])
            ->map(fn($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return AuthAttempt::query()
            ->where(function ($query) use ($user, $logins) {
                $query->where('user_id', $user->id);

                if (!empty($logins)) {
                    $query->orWhereIn('login', $logins);
                }
            })
            ->orderByDesc('attempted_at')
            ->orderByDesc('id')
            ->paginate($per_page)
            ->withQueryString();
    }

    /**
     * Создать пользователя.
     *
     * Новому пользователю выдаются права публичной части DEFAULT_ACCESSES.
     *
     * @param array $input
     * @param User $actor кто создаёт
     * @return User
     * @throws ValidationException
     */
    public static function create(array $input, User $actor): User
    {
        $data = self::validate($input, null, $actor);

        return DB::transaction(function () use ($data) {
            $user = new User();
            self::fill($user, $data);
            $user->password = Hash::make($data['password']);
            $user->initials = self::initials($user->login);
            $user->save();

            self::grantDefaultAccesses($user);
            self::flush($user->id);

            return $user;
        });
    }

    /**
     * Изменить пользователя. Пустой пароль — не менять.
     *
     * @param User $user
     * @param array $input
     * @param User $actor кто изменяет
     * @return User
     * @throws ValidationException
     */
    public static function update(User $user, array $input, User $actor): User
    {
        $data = self::validate($input, $user, $actor);

        self::fill($user, $data);

        if (!empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (empty($user->initials)) {
            $user->initials = self::initials($user->login);
        }

        $user->save();
        self::flush($user->id);

        return $user;
    }

    /**
     * Разрешить или запретить пользователю переключать тему оформления
     *
     * @param User $user
     * @param bool $value
     * @return User
     */
    public static function setThemeSwitch(User $user, bool $value): User
    {
        $user->ui_theme_switch = $value;
        $user->save();

        return $user;
    }

    /**
     * Открыть или скрыть пользователю журнал изменений сущностей (patch v29).
     * Администратору панели журнал доступен всегда — флаг для него не важен.
     *
     * @param User $user
     * @param bool $value
     * @return User
     */
    public static function setLogView(User $user, bool $value): User
    {
        $user->log_view = $value;
        $user->save();

        return $user;
    }

    /**
     * Мягко удалить пользователя.
     *
     * Строки прав, настроек и журнала не трогаем — восстановление вернёт всё
     * как было. remember_token обнуляем, чтобы «запомнить меня» не пустило
     * после восстановления старой cookie.
     *
     * @param User $user
     * @param User $actor
     * @return void
     * @throws ValidationException
     */
    public static function delete(User $user, User $actor): void
    {
        if ((int) $user->id === (int) $actor->id) {
            throw ValidationException::withMessages(['user' => 'Нельзя удалить самого себя.']);
        }

        if ($user->trashed()) {
            throw ValidationException::withMessages(['user' => 'Пользователь уже удалён.']);
        }

        DB::transaction(function () use ($user) {
            $user->remember_token = null;
            $user->save();
            $user->delete();
        });

        self::flush($user->id);
    }

    /**
     * Восстановить мягко удалённого пользователя
     *
     * @param User $user
     * @return void
     * @throws ValidationException
     */
    public static function restore(User $user): void
    {
        if (!$user->trashed()) {
            throw ValidationException::withMessages(['user' => 'Пользователь не удалён.']);
        }

        // логин или email мог занять кто-то, заведённый после удаления
        foreach (['login' => 'логином', 'email' => 'email'] as $field => $label) {
            $value = trim((string) $user->{$field});

            if ($value !== '' && User::where($field, $value)->where('id', '!=', $user->id)->exists()) {
                throw ValidationException::withMessages([
                    $field => 'Восстановить нельзя: с таким ' . $label . ' уже есть другой пользователь.',
                ]);
            }
        }

        $user->restore();
        self::flush($user->id);
    }

    /**
     * Сбросить кэш прав и меню пользователя
     *
     * @param int $id
     * @return void
     */
    public static function flush(int $id): void
    {
        cache()->forget('can_do_' . $id);
        cache()->forget('menu_tree_' . $id);
    }

    /**
     * Короткое название браузера и системы по user_agent
     *
     * @param string|null $agent
     * @return string
     */
    public static function browser(?string $agent): string
    {
        $agent = (string) $agent;

        if ($agent === '') {
            return '—';
        }

        $browsers = [
            'YaBrowser' => 'Яндекс Браузер',
            'Edg' => 'Edge',
            'OPR' => 'Opera',
            'Firefox' => 'Firefox',
            'Chrome' => 'Chrome',
            'Safari' => 'Safari',
        ];

        $systems = [
            'Windows' => 'Windows',
            'Android' => 'Android',
            'iPhone' => 'iOS',
            'iPad' => 'iPadOS',
            'Mac OS' => 'macOS',
            'Linux' => 'Linux',
        ];

        $browser = collect($browsers)->first(fn($label, $needle) => str_contains($agent, $needle . '/'));
        $system = collect($systems)->first(fn($label, $needle) => str_contains($agent, $needle));

        $label = trim(($browser ?? '') . ($browser && $system ? ' · ' : '') . ($system ?? ''));

        return $label !== '' ? $label : Str::limit($agent, 40);
    }

    /**
     * Проверить поля формы
     *
     * @param array $input
     * @param User|null $user null — создание
     * @param User $actor
     * @return array
     * @throws ValidationException
     */
    protected static function validate(array $input, ?User $user, User $actor): array
    {
        $input['login'] = trim((string) ($input['login'] ?? ''));
        $input['email'] = trim((string) ($input['email'] ?? ''));

        foreach (['active', 'is_admin', 'ui_theme_switch', 'log_view'] as $flag) {
            $input[$flag] = filter_var($input[$flag] ?? false, FILTER_VALIDATE_BOOLEAN);
        }

        $validator = Validator::make($input, [
            'name' => 'required|string|max:50',
            'last_name' => 'nullable|string|max:64',
            'second_name' => 'nullable|string|max:64',
            'login' => 'required|string|max:64',
            'email' => 'nullable|email|max:255',
            'password' => ($user ? 'nullable' : 'required') . '|string|min:8|max:100',
            'work_position' => 'nullable|string|max:100',
            'work_department' => 'nullable|string|max:100',
            'personal_mobile' => 'nullable|string|max:64',
        ], [
            // в lang/ru нет части сообщений (например email) — задаём явно
            'required' => 'Заполните поле «:attribute».',
            'email' => 'Email указан неверно.',
            'min' => 'Поле «:attribute» — не короче :min символов.',
            'max' => 'Поле «:attribute» — не длиннее :max символов.',
            'string' => 'Поле «:attribute» заполнено неверно.',
        ], [
            'name' => 'Имя',
            'last_name' => 'Фамилия',
            'second_name' => 'Отчество',
            'login' => 'Логин',
            'email' => 'Email',
            'password' => 'Пароль',
            'work_position' => 'Должность',
            'work_department' => 'Подразделение',
            'personal_mobile' => 'Телефон',
        ]);

        $validator->after(function ($validator) use ($input, $user, $actor) {
            // уникальность — с учётом удалённых: строка в users у них остаётся
            self::checkUnique($validator, 'login', $input['login'], $user, 'логином');
            self::checkUnique($validator, 'email', $input['email'], $user, 'email');

            if ($user && (int) $user->id === (int) $actor->id) {
                if (!$input['is_admin']) {
                    $validator->errors()->add('is_admin', 'Нельзя снять доступ в админ-панель с самого себя.');
                }

                if (!$input['active']) {
                    $validator->errors()->add('active', 'Нельзя отключить самого себя.');
                }
            }
        });

        $validator->validate();

        return $input;
    }

    /**
     * Проверка уникальности логина / email среди всех, включая удалённых
     *
     * @param \Illuminate\Validation\Validator $validator
     * @param string $field
     * @param string $value
     * @param User|null $user
     * @param string $label
     * @return void
     */
    protected static function checkUnique($validator, string $field, string $value, ?User $user, string $label): void
    {
        if ($value === '') {
            return;
        }

        $other = User::withTrashed()
            ->where($field, $value)
            ->when($user, fn($query) => $query->where('id', '!=', $user->id))
            ->first();

        if (empty($other)) {
            return;
        }

        $validator->errors()->add($field, $other->trashed()
            ? 'Есть удалённый пользователь с таким ' . $label . ' (' . $other->full_name . ') — восстановите его.'
            : 'Пользователь с таким ' . $label . ' уже есть: ' . $other->full_name . '.');
    }

    /**
     * Перенести поля формы в модель (без пароля)
     *
     * @param User $user
     * @param array $data
     * @return void
     */
    protected static function fill(User $user, array $data): void
    {
        foreach (['name', 'last_name', 'second_name', 'login', 'work_position', 'work_department', 'personal_mobile'] as $field) {
            $value = isset($data[$field]) ? trim((string) $data[$field]) : '';
            $user->{$field} = $value !== '' ? $value : null;
        }

        // email в таблице NOT NULL: у пользователей без почты хранится пустая строка
        $user->email = $data['email'];
        $user->active = $data['active'] ? 1 : 0;
        $user->is_admin = $data['is_admin'];
        $user->ui_theme_switch = $data['ui_theme_switch'];
        $user->log_view = $data['log_view']; // patch v29: доступ к журналу изменений

        // колонка full_name нужна сортировкам выпадающих списков (UserRepository::getAll)
        $user->full_name = trim(preg_replace('/\s+/u', ' ', $user->name . ' ' . $user->last_name . ' ' . $user->second_name));
    }

    /**
     * Выдать права публичной части без дублей
     *
     * @param User $user
     * @return void
     */
    protected static function grantDefaultAccesses(User $user): void
    {
        $ids = Access::whereIn('code', self::DEFAULT_ACCESSES)->pluck('id');

        foreach ($ids as $access_id) {
            $exists = DB::table('access_user')
                ->where('user_id', $user->id)
                ->where('access_id', $access_id)
                ->exists();

            if (!$exists) {
                DB::table('access_user')->insert([
                    'access_id' => $access_id,
                    'user_id' => $user->id,
                    'mode' => 1,
                ]);
            }
        }
    }

    /**
     * Инициалы латиницей по логину, как у существующих: n.kruchinina → NK
     *
     * @param string|null $login
     * @return string|null
     */
    protected static function initials(?string $login): ?string
    {
        $parts = preg_split('/[^a-z]+/i', (string) $login, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($parts)) {
            return null;
        }

        return Str::upper(collect($parts)->take(2)->map(fn($part) => $part[0])->implode(''));
    }
}
