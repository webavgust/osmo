<?php

namespace App\Modules\Pub\ProposalPdfTemplate\Controllers\Api;

use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\ProposalPdfTemplate\Repositories\ProposalPdfTemplateRepository;
use Illuminate\Http\Request;

class ApiProposalPdfTemplateController
{

    public function store(Request $request, Proposal $proposal, int $iteration = 1)
    {

        $proposal = ProposalRepository::getOnce($proposal->group, $iteration);
        if(empty($proposal)) abort(404);

        $request->validate([
            'name' => 'required|string',
            'html' => 'required|string',
        ]);

        ProposalPdfTemplateRepository::store(proposal: $proposal, data: $request->all());

        return ['result' => 'success'];
    }

}
