<?php

namespace App\Modules\Pub\Contract\Controllers\Api;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalGrade;
use App\Modules\Pub\Proposal\Models\ProposalType;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use Illuminate\Http\Request;

class ApiContractController
{
    public function store(Request $request, Partner $partner)
    {
        $request->validate([
            'organization' => 'required|exists:organizations,id',
            'type' => 'required|in:' . collect(ContractType::cases())->pluck('value')->join(','),
            'number' => 'nullable',
            'date' => 'nullable|date',
            'cb_signed' => 'nullable|bool',
        ]);

        $data = $request->all();
        ContractRepository::create(partner: $partner, data: $request->all());

        return ['result' => 'success'];
    }


    public function update(Request $request, Contract $contract)
    {
        $request->validate([
            'organization' => 'required|exists:organizations,id',
            'proposal' => 'nullable',
            'type' => 'required|in:' . collect(ContractType::cases())->pluck('value')->join(','),
            'number' => 'nullable',
            'date' => 'nullable|date',
            'cb_signed' => 'nullable|bool',
            'proposal_name' => 'nullable|string',
        ]);

        $data = $request->all();
        if(!empty($data['proposal']) && empty($contract->company->proposals()->find($data['proposal'])))
            abort(404);

        ContractRepository::update(contract: $contract, data: $request->all());

        return ['result' => 'success'];
    }

    public function delete(Contract $contract)
    {
        ContractRepository::delete(contract: $contract);

        return ['result' => 'success'];
    }
}
