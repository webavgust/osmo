<?php

namespace App\Modules\Pub\ScenarioGroup\Policies;

use App\Modules\Pub\User\Models\User;
use Illuminate\Auth\Scenario\HandlesAuthorization;

class ScenarioGroupPolicy
{
    use HandlesAuthorization;

    static public function scenario_create(User $user) {
        return $user->can_do('scenario_create');
    }

    static public function scenario_view(User $user) {
        return $user->can_do('scenario_view');
    }

    static public function general_scenario(User $user) {
        return $user->can_do('general_scenario');
    }


}
