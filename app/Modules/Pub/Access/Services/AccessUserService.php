<?php

namespace App\Modules\Pub\Access\Services;

use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AccessUserService
{
    private $uid;
    private $user;
    private $accesses;

    public function __construct($uid)
    {
        $this->uid = $uid;
        $this->user = !empty($this->uid) ? User::find($this->uid) : null;
        $this->accesses = cache()->get('can_do_' . $this->uid);
        $this->is_admin = $this->check(Access::find(6), true);

        if (empty($this->accesses))
            $this->refresh();
    }

    /**
     * геттер для списка доступов
     *
     * @return \Illuminate\Contracts\Cache\Repository|mixed
     */
    public function list()
    {
        return $this->accesses;
    }

    /**
     * Проверка доступа
     *
     * @param $access Доступ
     * @param $ignore_admin Игнорировать проверку на админа
     * @return bool|mixed|null
     */
    public function check($access, $ignore_admin = false)
    {
        // Если админ, дадим доступ (в зависимости от нужно поведения)
        if ($access?->id !== 6 && !empty($this->is_admin) && $this->is_admin) {
            return $access->admin_invert == 0;
        }

        # USER
        $a = DB::table('users')
            ->select('mode')
            ->where('id', $this->user->id ?? 0)
            ->leftJoin('access_user', 'id', '=', 'user_id')
            ->where('access_id', $access->id)
            ->value('mode');
        if (empty($from)) {
            if (abs($a) == 1) {
                return $a == 1;
            }
        }

        #GROUP
        $a = DB::table('users')
            ->select('mode')
            ->where('users.id', $this->user->id ?? 0)
            ->leftJoin('user_user_group', 'users.id', '=', 'user_id')
            ->leftJoin('user_groups', 'user_groups.id', '=', 'user_user_group.user_group_id')
            ->leftJoin('access_user_group', 'user_user_group.user_group_id', '=', 'access_user_group.user_group_id')
            ->where('access_id', $access->id)
            ->value('mode');
        if (empty($from)) {
            if (abs($a) == 1) return $a == 1;
        } elseif ($a) {
            return $a;
        }

        #DEPARTMENT
        $a = DB::table('users')
            ->select('mode')
            ->where('users.id', $this->user->id ?? 0)
            ->leftJoin('user_user_department', 'users.id', '=', 'user_id')
            ->leftJoin('user_departments', 'user_departments.id', '=', 'user_user_department.user_department_id')
            ->leftJoin('access_user_department', 'user_user_department.user_department_id', '=', 'access_user_department.user_department_id')
            ->where('access_id', $access->id)
            ->value('mode');
        if (empty($from)) {
            return $a == 1;
        } else {
            return $a;
        }
    }

    /**
     * Обновление доступов
     *
     * @param $ignore_admin Игнорировать админа
     * @return false|void
     */
    public function refresh($ignore_admin = false)
    {
        if (empty($this->uid)) return false;
        $arAccessCan = [];

        // сначала супер админ
        foreach (Access::all() as $access) {
            $arAccessCan[$access->code] = $this->check($access, $ignore_admin);
        }
        $this->accesses = $arAccessCan;
        cache()->forget('can_do_' . $this->uid);
        cache()->rememberForever('can_do_' . $this->uid, function () use ($arAccessCan) {
            return $arAccessCan;
        });
    }

    /**
     * Функция проверки разрешения для текущего пользователя
     *
     * @param $access Доступ
     * @return bool
     */
    public function can_do($access)
    {
        if (is_object($access)) $access = $access->chr;
        if (empty($this->accesses))
            $this->refresh();

        return !empty($this->accesses) && !empty($this->accesses[$access]) && $this->accesses[$access] == 1;
    }


    public static function getUsersByAccess(Access $access)
    {
        $users = $access->users->pluck('id');

        foreach ($access->user_groups as $group) {
            $users = $users->merge($group->users->pluck('id'));
        }

        return $users->toArray();
    }
}
