<?php

namespace App\Modules\Pub\Neuroservice\Services;

use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class NeuroserviceUserService
{
    private $uid;
    private $user;
    private $neuroservices;

    public function __construct($uid)
    {
        $this->uid = $uid;
        $this->user = !empty($this->uid) ? User::find($this->uid) : null;
        $this->neuroservices = cache()->get('can_do_' . $this->uid);
        $this->is_admin = $this->check(Neuroservice::find(6), true);

        if (empty($this->neuroservices))
            $this->refresh();
    }

    /**
     * геттер для списка доступов
     *
     * @return \Illuminate\Contracts\Cache\Repository|mixed
     */
    public function list()
    {
        return $this->neuroservices;
    }

    /**
     * Проверка доступа
     *
     * @param $neuroservice Доступ
     * @param $ignore_admin Игнорировать проверку на админа
     * @return bool|mixed|null
     */
    public function check($neuroservice, $ignore_admin = false)
    {
        // Если админ, дадим доступ (в зависимости от нужно поведения)
        if ($neuroservice?->id !== 6 && !empty($this->is_admin) && $this->is_admin) {
            return $neuroservice->admin_invert == 0;
        }

        # USER
        $a = DB::table('users')
            ->select('mode')
            ->where('id', $this->user->id ?? 0)
            ->leftJoin('neuroservice_user', 'id', '=', 'user_id')
            ->where('neuroservice_id', $neuroservice->id)
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
            ->leftJoin('neuroservice_user_group', 'user_user_group.user_group_id', '=', 'neuroservice_user_group.user_group_id')
            ->where('neuroservice_id', $neuroservice->id)
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
            ->leftJoin('neuroservice_user_department', 'user_user_department.user_department_id', '=', 'neuroservice_user_department.user_department_id')
            ->where('neuroservice_id', $neuroservice->id)
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
        $arNeuroserviceCan = [];

        // сначала супер админ
        foreach (Neuroservice::all() as $neuroservice) {
            $arNeuroserviceCan[$neuroservice->code] = $this->check($neuroservice, $ignore_admin);
        }
        $this->neuroservices = $arNeuroserviceCan;
        cache()->forget('can_do_' . $this->uid);
        cache()->rememberForever('can_do_' . $this->uid, function () use ($arNeuroserviceCan) {
            return $arNeuroserviceCan;
        });
    }

    /**
     * Функция проверки разрешения для текущего пользователя
     *
     * @param $neuroservice Доступ
     * @return bool
     */
    public function can_do($neuroservice)
    {
        if (is_object($neuroservice)) $neuroservice = $neuroservice->chr;
        if (empty($this->neuroservices))
            $this->refresh();

        return !empty($this->neuroservices) && !empty($this->neuroservices[$neuroservice]) && $this->neuroservices[$neuroservice] == 1;
    }


    public static function getUsersByNeuroservice(Neuroservice $neuroservice)
    {
        $users = $neuroservice->users->pluck('id');

        foreach ($neuroservice->user_groups as $group) {
            $users = $users->merge($group->users->pluck('id'));
        }

        return $users->toArray();
    }
}
