<?php

namespace App\Modules\Pub\Menu\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class MenuPolicy
{
    use HandlesAuthorization;

    public function menu_control(User $user)
    {
        return $user->can_do('menu_control');
    }

    public function menu_view(User $user)
    {
        return $user->can_do('menu_view');
    }
}


