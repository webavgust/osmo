<?php

namespace App\Modules\Pub\ProposalVariantExtraPay\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Currency\Models\Currency;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Hardware\Repository\HardwareRepository;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Repositories\NeuroserviceRepository;
use App\Modules\Pub\Proposal\Controllers\ProposalUpdateRequest;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\User\Models\User;
use App\Http\Controllers\Controller;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use Illuminate\Support\Facades\View;

class ProposalVariantExtraPayController extends Controller
{
    public function box_edit(ProposalVariant $variant)
    {
        $proposal = $variant->proposal;
        $currency = CurrencyRepository::get($proposal->currency_slug ?? Currency::CURRENCY_DEFAULT);

        $template = View::make('pub.proposal_variant_extra_pay.boxes.edit', [
            'variant' => $variant,
            'currency' => $currency,
            'cost_software' => $variant->neuro_cost_total + $variant->platform_cost_total + $variant->neuro_nds_cost_total + $variant->platform_nds_cost_total,
            'cost_work' => $variant->work_cost_total + $variant->work_nds_cost_total,
        ]);

        return $template;
    }

}
