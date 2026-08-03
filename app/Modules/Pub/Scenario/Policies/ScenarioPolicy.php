<?php

namespace App\Modules\Pub\Scenario\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Scenario\HandlesAuthorization;

class ScenarioPolicy
{
    use HandlesAuthorization;

    /**
     * Доступ на создание доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function scenario_create(User $user)
    {
        return $user->can_do('scenario_create');
    }

    /**
     * Доступ на просмотр доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function scenario_view(User $user)
    {
        return $user->can_do('scenario_view');
    }

    /**
     * Доступ на установку доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function scenario_set(User $user)
    {
        return $user->can_do('scenario_set');
    }
}
