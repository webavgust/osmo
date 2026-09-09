<?php

namespace App\Modules\Pub\Scenario\Services;

use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ScenarioUserService
{
    private $uid;
    private $user;
    private $scenarios;

    public function __construct($uid)
    {
        $this->uid = $uid;
        $this->user = !empty($this->uid) ? User::find($this->uid) : null;
        $this->scenarios = cache()->get('can_do_' . $this->uid);
        $this->is_admin = $this->check(Scenario::find(6), true);

        if (empty($this->scenarios))
            $this->refresh();
    }

    /**
     * геттер для списка доступов
     *
     * @return \Illuminate\Contracts\Cache\Repository|mixed
     */
    public function list()
    {
        return $this->scenarios;
    }

    /**
     * Проверка доступа
     *
     * @param $scenario Доступ
     * @param $ignore_admin Игнорировать проверку на админа
     * @return bool|mixed|null
     */
    public function check($scenario, $ignore_admin = false)
    {
        // Если админ, дадим доступ (в зависимости от нужно поведения)
        if ($scenario?->id !== 6 && !empty($this->is_admin) && $this->is_admin) {
            return $scenario->admin_invert == 0;
        }

        # USER
        $a = DB::table('users')
            ->select('mode')
            ->where('id', $this->user->id ?? 0)
            ->leftJoin('scenario_user', 'id', '=', 'user_id')
            ->where('scenario_id', $scenario->id)
            ->value('mode');
        if (empty($from)) {
            if (abs($a) == 1) {
                return $a == 1;
            }
        }

        // Ветки GROUP и DEPARTMENT убраны вместе с модулями UserGroup
        // и UserDepartment. Они и так были нерабочими: таблиц
        // scenario_user_group и scenario_user_department в базе нет,
        // и любое обращение сюда падало бы SQL-ошибкой.
        return false;
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
        $arScenarioCan = [];

        // сначала супер админ
        foreach (Scenario::all() as $scenario) {
            $arScenarioCan[$scenario->code] = $this->check($scenario, $ignore_admin);
        }
        $this->scenarios = $arScenarioCan;
        cache()->forget('can_do_' . $this->uid);
        cache()->rememberForever('can_do_' . $this->uid, function () use ($arScenarioCan) {
            return $arScenarioCan;
        });
    }

    /**
     * Функция проверки разрешения для текущего пользователя
     *
     * @param $scenario Доступ
     * @return bool
     */
    public function can_do($scenario)
    {
        if (is_object($scenario)) $scenario = $scenario->chr;
        if (empty($this->scenarios))
            $this->refresh();

        return !empty($this->scenarios) && !empty($this->scenarios[$scenario]) && $this->scenarios[$scenario] == 1;
    }


    public static function getUsersByScenario(Scenario $scenario)
    {
        // Группы доступа (UserGroup) удалены — остались прямые назначения.
        return $scenario->users->pluck('id')->toArray();
    }
}
