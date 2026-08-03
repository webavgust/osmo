<?php

namespace App\Modules\Pub\NeuroserviceGroup\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Neuroservice\HandlesAuthorization;

class NeuroserviceGroupPolicy
{
    use HandlesAuthorization;

    static public function neuroservice_create(User $user) {
        return $user->can_do('neuroservice_create');
    }

    static public function neuroservice_view(User $user) {
        return $user->can_do('neuroservice_view');
    }

    static public function general_neuroservice(User $user) {
        return $user->can_do('general_neuroservice');
    }


}
