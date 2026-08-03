<?php

namespace App\Modules\Pub\Payment\Controllers\Api;

use App\Modules\Pub\Company\Models\Company;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Contract\Models\Contract;
use App\Modules\Pub\Contract\Models\ContractType;
use App\Modules\Pub\Contract\Repositories\ContractRepository;
use App\Modules\Pub\ContractSpecification\Models\ContractSpecification;
use App\Modules\Pub\Payment\Repositories\PaymentRepository;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Models\ProposalGrade;
use App\Modules\Pub\Proposal\Models\ProposalType;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Requests\ProposalRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use Illuminate\Http\Request;

class ApiPaymentController
{
    public function store(Request $request, ContractSpecification $spec)
    {
        $request->validate([
            'payment' => 'required|array',
            'payment.*.is_unknown' => 'nullable|boolean',
            'payment.*.date_plan' => 'nullable|date_format:Y-m-d',
            'payment.*.amount_plan' => 'nullable|numeric|required_if:payment.*.amount_fact,""',
            'payment.*.date_fact' => 'nullable|date_format:Y-m-d',
            'payment.*.amount_fact' => 'nullable|numeric|required_if:payment.*.amount_plan,""',
            'payment.*.user_id' => 'nullable|numeric',
        ]);

        $data = collect($request->input('payment'))->filter(function ($row) {
            return !empty($row['amount_plan']) || !empty($row['amount_fact']);
        })->toArray();


        PaymentRepository::create(spec: $spec, data: $data);

        return ['result' => 'success'];
    }

}
