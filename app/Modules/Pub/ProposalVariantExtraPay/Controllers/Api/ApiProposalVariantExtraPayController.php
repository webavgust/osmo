<?php

namespace App\Modules\Pub\ProposalVariantExtraPay\Controllers\Api;

use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalGrade;
use App\Modules\Pub\Proposal\Models\ProposalType;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\ProposalVariant\Models\ProposalVariant;
use App\Modules\Pub\ProposalVariantExtraPay\Services\ProposalVariantExtraPayService;
use Illuminate\Http\Request;

class ApiProposalVariantExtraPayController
{
    public $repo;
    public function __construct()
    {
        $this->repo = new ProposalRepository();
    }

    public function store(Request $request, ProposalVariant $variant)
    {
        $request->validate([
            'data' => 'required|array',
            'cb_all' => 'nullable|boolean',
            'data.percent' => 'required|array',
            'data.percent.*' => 'required|array',
            'data.percent.*.name' => 'nullable|string',
            'data.percent.*.type' => 'required|in:all,software,work',
            'data.percent.*.amount' => 'required|numeric|min:0|max:100'

        ]);

        if($request->input('cb_all')) {
            $variant->proposal->variants->each(function($variant) use ($request) {
                ProposalVariantExtraPayService::create(variant: $variant, data: $request->input('data'));
            });
        } else {
            ProposalVariantExtraPayService::create(variant: $variant, data: $request->input('data'));
        }

        return ['result' => 'success'];
    }


}
