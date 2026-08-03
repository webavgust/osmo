<?php

namespace App\Modules\Pub\ProposalVariant\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariantExtraPay\Models\ProposalVariantExtraPay;
use App\Modules\Pub\ProposalVariantPlatform\Models\ProposalVariantPlatform;
use App\Modules\Pub\ProposalVariantScenario\Models\ProposalVariantScenario;
use App\Modules\Pub\ProposalVariantSoftware\Models\ProposalVariantSoftware;
use App\Modules\Pub\ProposalVariantWork\Models\ProposalVariantWork;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class ProposalVariant extends ModuleModel
{
    protected $fillable = ['is_main', 'period_type', 'period_value', 'cost_total_base',
        'discount_customer', 'discount_partner_p', 'discount_partner', 'cost_total',
        'soft_cost_total_base', 'soft_discount_partner', 'soft_cost_total', 'soft_discount_customer', 'soft_discount_partner_p',
        'work_cost_total_base', 'work_discount_partner', 'work_cost_total', 'work_discount_customer',
        'neuro_cost_total_base', 'neuro_discount_customer', 'neuro_discount_partner', 'neuro_cost_total','neuro_discount_partner_p',
        'platform_cost_total_base', 'platform_discount_customer', 'platform_discount_partner', 'platform_cost_total', 'platform_discount_partner_p',

        'cost_total', 'neuro_nds_cost_total', 'soft_nds_cost_total', 'work_nds_cost_total', 'nds_cost_total', 'platform_nds_cost_total',
        'task'
    ];




    protected $casts = ['is_main' => 'bool'];


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

            $instance->proposal_scenarios->each(function($sub_instance) {
                $sub_instance->delete();
            });
            $instance->proposal_works->each(function($sub_instance) {
                $sub_instance->delete();
            });
            $instance->proposal_software->each(function($sub_instance) {
                $sub_instance->delete();
            });
        });
    }


    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function proposal_platforms()
    {
        return $this->hasMany(ProposalVariantPlatform::class)->orderBy('sort');
    }

    public function proposal_scenarios()
    {
        return $this->hasMany(ProposalVariantScenario::class)->orderBy('sort');
    }

    public function proposal_works()
    {
        return $this->hasMany(ProposalVariantWork::class);
    }

    public function proposal_software()
    {
        return $this->hasMany(ProposalVariantSoftware::class);
    }


    public function hardware()
    {
        return $this->hasMany(Hardware::class)->orderBy('sort', 'asc');
    }

    public function extra_pays()
    {
        return $this->hasMany(ProposalVariantExtraPay::class)->orderBy('sort');
    }


    public function getFinalPaymentAttribute()
    {
        if($this->extra_pays->isEmpty()) {
            return $this->platform_cost_total + $this->soft_cost_total + $this->neuro_cost_total + $this->neuro_nds_cost_total + $this->soft_nds_cost_total;
        } else {
            return $this->extra_pays->last()->software_end;
        }
    }

    public function getFinalPrepayAttribute()
    {
        if($this->extra_pays->isEmpty()) {
            return $this->work_cost_total + $this->work_nds_cost_total;
        } else {
            return $this->extra_pays->last()->work_end;
        }
    }
}
