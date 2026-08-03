<?php

namespace App\Modules\Pub\Constant\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ConstantPolicy
{
    use HandlesAuthorization;

    public function constant_control(User $user)
    {
        return $user->can_do('constant_control');
    }
}
