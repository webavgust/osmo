<?php

namespace App\Modules\Pub\ProposalVariantPlatform\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariantPlatform extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['sort', 'description', 'notice', 'cb_process', 'cb_nds', 'nds', 'cost', 'count', 'discount', 'cost_discount'];
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
        });
    }


    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }

    public function getCostTotalAttribute()
    {
        return $this->cost_discount * $this->count;
    }
}
