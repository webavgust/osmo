<?php

namespace App\Modules\Pub\ProposalPlatform\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;

class ProposalPlatform extends ModuleModel
{
    protected $fillable = ['cb_process', 'description', 'notice', 'sort', 'default_cost'];
    public $casts = ['default_cost' => 'json'];

    public $timestamps = false;

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


    public function proposal()
    {
        return $this->belongsTo(Proposal::class)->orderBy('sort');
    }


    public function proposal_variant()
    {
        return $this->hasMany(ProposalVariant::class)->withPivot(['default_cost_year', 'default_cost_unlimited', 'cost', 'count', 'discount', 'total']);
    }


}
