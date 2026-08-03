<?php

namespace App\Modules\Pub\Scenario\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\ScenarioGroup\Models\ScenarioGroup;
use App\Modules\Pub\Menu\Models\Menu;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Database\Eloquent\Builder;

class Scenario extends ModuleModel
{

    public static $module_name = 'Доступ';
    protected $fillable = ['active', 'cb_registered', 'name', 'work_scenario', 'work_result', 'event_reminder', 'cost_force', 'sort', 'cost_rules'];
    protected $casts = ['active' => 'bool', 'cb_registered' => 'bool', 'cost_force' => 'json', 'cost_rules' => 'json'];

    /**
     * Добавление сортировки по умолчанию
     *
     * @return void
     */
    static public function boot()
    {
        parent::boot();
        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('sort', 'asc');
        });
    }

    /*** RELATIONS ***/

    public function scenario_group()
    {
        return $this->belongsTo(ScenarioGroup::class);
    }

    public function neuroservices()
    {
        return $this->belongsToMany(Neuroservice::class)->orderBy('neuroservice_group_id')->orderBy('name');
    }

    public function getCostYearAttribute()
    {
        return $this->cost_force['year'] ?? $this->neuroservices->sum(function($item) {
            return $item->cost['year'] ?? 0;
        });
    }

    public function getCostUnlimitedAttribute()
    {
        if(!empty($this->cost_force['unlimited'])) {
            return $this->cost_force['unlimited'];
        } elseif($this->neuroservices->isNotEmpty()) {
            $multiplier = \App\Modules\Pub\Constant\Models\Constant::get('neuroservice_unlimited_multiplier');
            return $this->neuroservices->sum(function($item) use ($multiplier) {
                if(!empty($item->cost['unlimited'])) return $item->cost['unlimited'];

                return !empty($item->cost['year']) ? $item->cost['year'] * $multiplier : 0;
            });
        } elseif(!empty($this->cost_force['year'])) {
            $multiplier = \App\Modules\Pub\Constant\Models\Constant::get('neuroservice_unlimited_multiplier');
            return $this->cost_force['year'] * $multiplier;
        }
    }

    public function getFullNameAttribute()
    {
        return "[{$this->scenario_group->name}] " . $this->name;
    }

    public function getNumberAttribute()
    {
        $prefix = 'S'; $PID = $this->scenario_group_id; $ID = $this->id;
        return $prefix . '-' . str_pad($PID, 2, '0', STR_PAD_LEFT) . '-' . str_pad($ID, 3, '0', STR_PAD_LEFT);
    }
}
