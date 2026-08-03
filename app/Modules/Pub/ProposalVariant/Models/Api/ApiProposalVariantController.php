<?php

namespace App\Modules\Pub\ProposalVariant\Models\Api;

use App\Modules\Pub\Hardware\Models\Hardware;
use App\Modules\Pub\Hardware\Repository\HardwareRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalVariant\Repository\ProposalVariantRepository;
use App\Modules\Pub\Software\Models\Software;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\Software\Requests\ListFilterRequest;
use App\Modules\Pub\Software\Services\SoftwareListFilterService;
use App\Modules\Pub\Software\Services\SoftwareService;
use App\View\Components\Log\Story;
use App\View\Components\Proposal\HardwareTable;
use Illuminate\Http\Request;

class ApiProposalVariantController
{
    public function update(ProposalVariant $variant, Request $request)
    {
        $request->validate([
            'task' => 'nullable|string',
            'cb_all' => 'nullable|boolean',
        ]);

        ProposalVariantRepository::update($variant, $request->all());
        $variant->refresh();

        $view = view('components.proposal_variant.task', ['variant' => $variant]);

        return ['result' => 'success', 'html' => $view->render()];
    }
}
