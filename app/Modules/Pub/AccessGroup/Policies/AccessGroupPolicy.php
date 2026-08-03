<?php

namespace App\Modules\Pub\AccessGroup\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class AccessGroupPolicy
{
    use HandlesAuthorization;

    static public function access_create(User $user) {
        return $user->can_do('access_create');
    }

    static public function access_view(User $user) {
        return $user->can_do('access_view');
    }

    static public function general_access(User $user) {
        return $user->can_do('general_access');
    }


}
