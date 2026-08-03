<?php

namespace App\Modules\Pub\OrderTask\Policies;

use App\Modules\Pub\Order\Models\Order;
use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class OrderTaskPolicy
{
    use HandlesAuthorization;


    public function view(User $user)
    {
        return $user->can_do('order_task_view');
    }

    public function edit(User $user)
    {
        return $user->can_do('order_task_edit');
    }

    public function order_task_submit(User $user)
    {
        return $user->can_do('order_task_submit');
    }

    public function order_task_copy(User $user)
    {
        return $user->can_do('order_task_copy');
    }

    public function order_task_attach(User $user)
    {
        return $user->can_do('order_task_attach');
    }

    public function order_task_history(User $user)
    {
        return $user->can_do('order_task_history');
    }

    public function order_task_agree(User $user)
    {
        return $user->can_do('order_task_agree');
    }
}
