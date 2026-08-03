<?php

namespace App\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AppPolicy
{
    use HandlesAuthorization;

    public function access(User $user)
    {
        return $user->isAdmin();
    }

    public function super_user(User $user)
    {
        return $user->isAdmin() || $user->can_do('super_user');
    }
}
