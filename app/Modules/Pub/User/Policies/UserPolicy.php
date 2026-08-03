<?php

namespace App\Modules\Pub\User\Policies;

use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\User\Services\UserService;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Auth\Access\HandlesAuthorization;

class UserPolicy
{
    use HandlesAuthorization;

    public function view_profile(User $user)
    {
        return $user->can_do('users_view_profile');
    }

    public function view_catalog(User $user)
    {
        return $user->can_do('users_view_catalog');
    }

    public function groups_view_catalog(User $user)
    {
        return $user->can_do('users_groups_view_catalog');
    }

    public function departments_view_catalog(User $user)
    {
        return $user->can_do('users_departments_view_catalog');
    }

    /**
     * Имеет подчиненных
     *
     * @param User $user
     * @return bool
     */
    public function users_have_sub(User $user)
    {
        $repo = new UserRepository();

        return $user->can_do('users_have_sub') && count($repo->getSubUsers($user)) > 0;
    }

    public function users_sub_users_control(User $user)
    {
        return $user->can_do('users_sub_users_control');
    }


    public function direction_a(User $user = null)
    {
        if(empty($user))
            $user = auth()->user();

        return is_admin() || $user->groups->contains(
            UserGroup::DIRECTION_A,
        );
    }

    public function direction_b(User $user = null)
    {
        if(empty($user))
            $user = auth()->user();

        return is_admin() || $user->groups->contains(
                UserGroup::DIRECTION_B,
        );
    }

}
