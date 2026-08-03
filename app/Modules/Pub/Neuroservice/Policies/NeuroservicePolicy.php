<?php

namespace App\Modules\Pub\Neuroservice\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Neuroservice\HandlesAuthorization;

class NeuroservicePolicy
{
    use HandlesAuthorization;

    /**
     * Доступ на создание доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function neuroservice_create(User $user)
    {
        return $user->can_do('neuroservice_create');
    }

    /**
     * Доступ на просмотр доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function neuroservice_view(User $user)
    {
        return $user->can_do('neuroservice_view');
    }

    /**
     * Доступ на установку доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function neuroservice_set(User $user)
    {
        return $user->can_do('neuroservice_set');
    }
}
