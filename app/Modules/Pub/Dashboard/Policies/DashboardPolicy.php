<?php

namespace App\Modules\Pub\Dashboard\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class DashboardPolicy
{
    use HandlesAuthorization;


    public function desktop_ann(User $user)
    {
        return $user->can_do('super_user');
    }
}

