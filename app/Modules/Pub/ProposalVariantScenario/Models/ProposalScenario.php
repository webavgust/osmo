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
    protected $fillable = ['cost', 'count', 'discount', 'cost_total'];

    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }
    public function scenario()
    {
        return $this->belongsTo(Scenario::class);
    }

}
