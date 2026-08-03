<?php

namespace App\Modules\Pub\Neuroservice\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\DealScenario\Models\DealScenario;
use App\Modules\Pub\NeuroserviceGroup\Models\NeuroserviceGroup;
use App\Modules\Pub\Menu\Models\Menu;
use App\Modules\Pub\ProposalVariantScenario\Models\ProposalVariantScenario;
use App\Modules\Pub\Scenario\Models\Scenario;
use App\Modules\Pub\User\Models\User;
use App\Modules\Pub\UserDepartment\Models\UserDepartment;
use App\Modules\Pub\UserGroup\Models\UserGroup;
use Illuminate\Database\Eloquent\Builder;

class Neuroservice extends ModuleModel
{

    public static $module_name = 'Доступ';
    protected $fillable = ['cb_registered', 'name', 'tech_name', 'cost','sort'];
    protected $casts = ['cb_registered' => 'bool', 'cost' => 'json'];

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

    public function getCostYearAttribute() {
        return $this->cost['year'] ?? 0;
    }

    public function getCostUnlimitedAttribute() {
//        $multiplier = \App\Modules\Pub\Constant\Models\Constant::get('neuroservice_unlimited_multiplier');
        $multiplier = env('UNLIMITED_MULTIPLIER');

        if(!empty($this->cost['unlimited'])) return $this->cost['unlimited'];

        return !empty($this->cost['year']) ? $this->cost['year'] * $multiplier : 0;
    }

    public function getNameCostAttribute()
    {
        return $this->name . ' (' . $this->cost_year . ' | ' . $this->cost_unlimited . ')';
    }

    /*** RELATIONS ***/

    public function neuroservice_group()
    {
        return $this->belongsTo(NeuroserviceGroup::class);
    }

    public function scenarios()
    {
        return $this->belongsToMany(Scenario::class)->orderBy('id');
    }

    public function proposal_variant_scenarios()
    {
        return $this->belongsToMany(ProposalVariantScenario::class)->withPivot(['cost']);
    }

    public function getNumberAttribute()
    {
        $prefix = 'N'; $PID = $this->neuroservice_group_id; $ID = $this->id;
        return $prefix . '-' . str_pad($PID, 2, '0', STR_PAD_LEFT) . '-' . str_pad($ID, 3, '0', STR_PAD_LEFT);
    }
}
