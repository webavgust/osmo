<?php

namespace App\Modules\Pub\ContractSpecification\Controllers\Api;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecificationStatus;
use App\Modules\Pub\ContractSpecification\Repository\ContractSpecificationRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalGrade;
use App\Modules\Pub\Proposal\Models\ProposalType;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use Illuminate\Http\Request;

class ApiContractSpecificationController
{
    public function store(Request $request, Contract $contract)
    {
        $request->validate([
            'status' => 'required|in:' . implode(",", array_keys(ContractSpecificationStatus::getStatuses())),
            'company' => 'required|exists:companies,id',
            'name' => 'required',
//            'amount' => 'nullable|int',
            'cb_signed' => 'nullable|boolean',
            'currency' => 'required|exists:currencies,slug',
        ]);

        $data = $request->all();
        ContractSpecificationRepository::create(contract: $contract, data: $request->all());

        return ['result' => 'success'];
    }


    public function update(Request $request, ContractSpecification $spec)
    {
        $request->validate([
            'status' => 'required|in:' . implode(",", array_keys(ContractSpecificationStatus::getStatuses())),
            'company' => 'required|exists:companies,id',
            'contract' => 'required|exists:contracts,id',
            'name' => 'required',
//            'amount' => 'nullable|int',
            'scenario' => 'nullable|array',
            'scenario_manual' => 'nullable|array',
            'cb_signed' => 'nullable|boolean',
            'currency' => 'required|exists:currencies,slug',

        ]);

        $data = $request->all();

        ContractSpecificationRepository::update(spec: $spec, data: $request->all());

        return ['result' => 'success'];
    }

    public function delete(ContractSpecification $spec)
    {
        if(!$spec->canDelete()) abort(404);
        ContractSpecificationRepository::delete(spec: $spec);

        return ['result' => 'success'];
    }

    public function set_project_configuration(ContractSpecification $spec, Request $request)
    {
        $request->validate([
            'num' => 'required|int|min:0',
            'unbind' => 'nullable|boolean',
        ]);

        if($request->unbind) {
            $configuration = $spec->project_configurations()->where('id', $request->num)->first();
            ContractSpecificationRepository::detachProjectConfiguration($spec, $configuration);
        } else {
            $configuration = $spec->company->configurations_available->where('id', $request->num)->first();
            ContractSpecificationRepository::attachProjectConfiguration($spec, $configuration);
        }


        return ['result' => 'success'];
    }
}
