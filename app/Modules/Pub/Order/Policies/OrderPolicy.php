<?php

namespace App\Modules\Pub\Order\Policies;

use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderPolicy
{
    use HandlesAuthorization;


    public function view_detail(User $user)
    {
        return $user->can_do('order_view_detail');
    }

    public function set_curator(User $user)
    {
        return $user->can_do('order_became_curator');
    }

    public function info_company(User $user)
    {
        return $user->can_do('order_info_company');
    }

    public function order_curator(User $user, Order $order)
    {
        if($user->isAdmin()) return true;

        return $user->can_do('order_curator') && $order->curator_id == $user->id;
    }

    public function order_comments_control(User $user)
    {
        return $user->can_do('order_comments_control');
    }


    public function order_tech_leader(User $user)
    {
        return $user->can_do('order_tech_leader');
    }

    public function order_group_A(User $user)
    {
        return $user->can_do('order_group_A');
    }

    public function order_group_B(User $user)
    {
        return $user->can_do('order_group_B');
    }



    public function view(User $user)
    {
        return $user->can_do('order_view');
    }
}
