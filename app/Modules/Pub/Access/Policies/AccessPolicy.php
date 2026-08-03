<?php

namespace App\Modules\Pub\Access\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessPolicy
{
    use HandlesAuthorization;

    /**
     * Доступ на создание доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function access_create(User $user)
    {
        return $user->can_do('access_create');
    }

    /**
     * Доступ на просмотр доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function access_view(User $user)
    {
        return $user->can_do('access_view');
    }

    /**
     * Доступ на установку доступов
     *
     * @param User $user Пользователь
     * @return bool|mixed|null
     */
    static public function access_set(User $user)
    {
        return $user->can_do('access_set');
    }
}
