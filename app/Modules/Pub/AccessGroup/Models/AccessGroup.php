<?php

namespace App\Modules\Pub\AccessGroup\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Access\Models\Access;

class AccessGroup extends ModuleModel
{
    public static $module_name = 'Группа доступа';
    protected $fillable = ['name', 'prefix', 'icon', 'sort'];

    public function accesses()
    {
        return $this->hasMany(Access::class);
    }
}
