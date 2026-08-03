<?php

namespace App\Modules\Pub\ScenarioGroup\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Scenario\Models\Scenario;

class ScenarioGroup extends ModuleModel
{
    public static $module_name = 'Группа сценариев';
    protected $fillable = ['name', 'sort'];

    public function scenarios()
    {
        return $this->hasMany(Scenario::class)->orderBy('active', 'desc')->orderBy('name');
    }

    public function getHasEmptyAttribute()
    {
        return $this->scenarios()->whereDoesntHave('neuroservices')->count() > 0;
    }

    public function getNumberAttribute()
    {
        $prefix = 'S'; $ID = $this->id;
        return $prefix . '-' . str_pad($ID, 2, '0', STR_PAD_LEFT);
    }
}
