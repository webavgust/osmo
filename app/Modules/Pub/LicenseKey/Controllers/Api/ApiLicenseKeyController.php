<?php

namespace App\Modules\Pub\LicenseKey\Controllers\Api;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\ContractSpecification\Repository\ContractSpecificationRepository;
use App\Modules\Pub\LicenseKey\Models\LicenseKey;
use App\Modules\Pub\LicenseKey\Repository\LicenseKeyRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalGrade;
use App\Modules\Pub\Proposal\Models\ProposalType;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use Illuminate\Http\Request;

class ApiLicenseKeyController
{
    public function store(Request $request, Company $company)
    {
        $request->validate([
            'active' => 'nullable|boolean',
            'key' => 'required|string',
            'active_from' => 'required|date',
            'active_to' => 'required|date',
            'specification' => 'nullable|exists:contract_specifications,id',
        ]);

        $data = $request->all();
        LicenseKeyRepository::create(company: $company, data: $request->all());

        return ['result' => 'success'];
    }


    public function update(Request $request, LicenseKey $key)
    {
        $request->validate([
            'active' => 'nullable|boolean',
            'key' => 'required|string',
            'active_from' => 'required|date',
            'active_to' => 'required|date',
            'specification' => 'nullable|exists:contract_specifications,id',

        ]);

        $data = $request->all();

        LicenseKeyRepository::update(key: $key, data: $request->all());

        return ['result' => 'success'];
    }

    public function delete(LicenseKey $key)
    {
        LicenseKeyRepository::delete(key: $key);

        return ['result' => 'success'];
    }
}
