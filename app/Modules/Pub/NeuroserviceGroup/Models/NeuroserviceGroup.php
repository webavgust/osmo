<?php

namespace App\Modules\Pub\NeuroserviceGroup\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;

class NeuroserviceGroup extends ModuleModel
{
    public static $module_name = 'Группа доступа';
    protected $fillable = ['name', 'sort'];

    public function neuroservices()
    {
        return $this->hasMany(Neuroservice::class);
    }

    public function getNumberAttribute()
    {
        $prefix = 'N'; $ID = $this->id;
        return $prefix . '-' . str_pad($ID, 2, '0', STR_PAD_LEFT);
    }
}
