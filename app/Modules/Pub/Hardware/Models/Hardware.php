<?php

namespace App\Modules\Pub\Hardware\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;

class Hardware extends ModuleModel
{
    protected $fillable = ['name', 'count', 'params', 'sort'];

    public function proposal_variant()
    {
        return $this->belongsTo(ProposalVariant::class);
    }

}
