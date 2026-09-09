<?php

namespace App\Modules\Pub\ProposalVariant\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Deal\Models\Deal;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariantScenario\Models\ProposalVariantScenario;
use App\Modules\Pub\Scenario\Models\Scenario;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProposalVariant extends ModuleModel
{
    protected $fillable = ['period_type', 'period_value', 'cost_total_base',
        'discount_customer', 'discount_partner_p', 'discount_partner', 'cost_total'];

    public function proposal()
    {
        return $this->belongsTo(Proposal::class);
    }

    public function proposal_scenarios()
    {
        return $this->hasMany(ProposalVariantScenario::class);
    }

    public function neuroservices()
    {

    }
}
