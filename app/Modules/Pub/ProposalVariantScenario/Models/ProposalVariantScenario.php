<?php

namespace App\Modules\Pub\ProposalVariantScenario\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariantScenario extends ModuleModel
{
    public $timestamps = false;
        protected $fillable = ['sort', 'real_name', 'mnemonic_name', 'comment', 'cb_process', 'cb_nds', 'nds', 'cost', 'count', 'cost_total', 'discount', 'cost_discount', 'default_cost_year', 'default_cost_unlimited'];
    protected $casts = ['cb_process' => 'bool', 'cb_nds' => 'bool'];

    /**
     * Дополняем слушатели событий
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($instance) {
            // почистим variants
            $instance->neuroservices()->sync([]);
        });
    }


    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }
    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }

    public function neuroservices()
    {
        return $this->belongsToMany(Neuroservice::class)->withPivot(['cost']);
    }

    public function getCostSavedAttribute()
    {
        $ret = ['year' => 0, 'unlimited' => 0];
        $this->neuroservices->each(function($neuro) use (&$ret) {
            $ret['year'] += $neuro->cost['year'];
            $ret['unlimited'] += $neuro->cost['unlimited'] ?? neuro->cost['year'];
        });

        return $ret;
    }

    public function getCostTotalAttribute()
    {
        return $this->cost_discount * $this->count;
    }
}
