<?php

namespace App\Modules\Pub\ProposalVariantExtraPay\Models;

use App\Models\ModuleModel;
use App\Modules\Pub\Client\Services\ClientService;
use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\ProposalPdfTemplate\Models\ProposalPdfTemplate;
use App\Modules\Pub\ProposalPlatform\Models\ProposalPlatform;
use App\Modules\Pub\ProposalSoftware\Models\ProposalSoftware;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalWork\Models\ProposalWork;
use App\Modules\Pub\Sector\Models\Sector;
use App\Modules\Pub\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

class ProposalVariantExtraPay extends ModuleModel
{
    protected $fillable = ['name', 'block', 'type', 'percent', 'value', 'base', 'software_start', 'software_end', 'work_start', 'work_end','total', 'currency', 'sort'];


    /*** RELATIONS ***/

    public function variant()
    {
        return $this->belongsTo(ProposalVariant::class, 'proposal_variant_id')->orderBy('sort');
    }
}
