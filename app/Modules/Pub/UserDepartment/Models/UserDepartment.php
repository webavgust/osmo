<?php

namespace App\Modules\Pub\UserDepartment\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Access\Models\Access;
use App\Modules\Pub\ChangeLogger\Traits\HasLogger;
use App\Modules\Pub\User\Models\User;
use App\Traits\Eloquent\Model\FindOrCreate;

class UserDepartment extends ModuleModel
{
    use HasLogger;
    use FindOrCreate;

    public static $module_name = 'Подразделение пользователя';

    public $fillable = [
        "id", "active", "name"
    ];

    /*** RELATIONS ***/
    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function accesses()
    {
        return $this->belongsToMany(Access::class)->withPivot('mode');
    }

    public function agreementers()
    {
        return $this->belongsToMany(User::class, 'user_department_user');
    }
}
