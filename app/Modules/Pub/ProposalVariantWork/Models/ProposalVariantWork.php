<?php

namespace App\Modules\Pub\ProposalVariantWork\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalWork\Models\ProposalWork;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariantWork extends ModuleModel
{
    public $timestamps = false;
    protected $fillable = ['cb_nds', 'nds', 'cost', 'count', 'discount_customer', 'cb_partner_discount', 'discount', 'total', 'discount_partner'];
    protected $casts = ['cb_nds' => 'bool', 'cb_partner_discount' => 'bool'];
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

    public function proposal_work()
    {
        return $this->belongsTo(ProposalWork::class);
    }

    public function getCostDiscountAttribute()
    {
        return round(($this->cost - $this->discount), 2);
    }
}
