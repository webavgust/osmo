<?php

namespace App\Modules\Pub\UserGroup\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\User\Models\User;
use App\Traits\Eloquent\Model\FindOrCreate;

class UserGroup extends ModuleModel
{
    use FindOrCreate;


    const GROUP_ADMIN = 1;

    const GROUP_MANAGER = -1; #DEPRECATED
    const GROUP_CURATOR = -1; #DEPRECATED
    const GROUP_CURATOR_DEFAULT = -1; #DEPRECATED
    const GROUP_EXECUTER_ECO_LAB = -1; #DEPRECATED
    const GROUP_FINAL_AGREEMENTERS = -1 ; #DEPRECATED


    // согласовант
    const GROUP_AGREEMENT = 62;
    const GROUP_EVALUATION = 54;
    const GROUP_FIN_SUPERVISOR = 60;



    const GROUP_SUPERVISOR = 9;
    const GROUP_LAB_SUPERVISOR = 66;
    const GROUP_LAB_SUB_SUPERVISOR_BY_DIRECTION = 67;
    const GROUP_CURATOR_BY_DIRECTION = 68;
    const GROUP_EXECUTOR_BY_DIRECTION = 69;


    const GROUP_SAMPLER = 70;

    const GROUP_ANALYTIC = 71;

    const DIRECTION_A = 73;
    const DIRECTION_B = 74;

    public static $module_name = 'Группа пользователя';

    public $fillable = [
        "id", "active", "name", "description"
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)->orderBy('last_name', 'asc');
    }

    public function accesses() {
            return $this->belongsToMany(Access::class)->withPivot('mode');
    }
}
