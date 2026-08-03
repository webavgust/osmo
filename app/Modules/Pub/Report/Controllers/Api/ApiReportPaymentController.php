<?php

namespace App\Modules\Pub\Report\Controllers\Api;

use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Report\Services\ReportPaymentFilterService;
use Illuminate\Http\Request;

class ApiReportPaymentController
{
    public function filter(Request $request)
    {
        $data = $request->validate([
            'company' => 'nullable|exists:companies,id',
            'user_id' => 'nullable|array',
            'user_id.*' => 'nullable|exists:users,id',
            'contract_number' => 'nullable|string',
            'date_both' => 'nullable|string',
            'date_plan' => 'nullable|string',
            'date_fact' => 'nullable|string',
            'date_realization' => 'nullable|string',
            'amount_fact' => 'nullable|array',
            'pay_mode' => 'nullable|in:payed,unpayed',
            'amount_fact.from' => 'nullable|numeric',
            'amount_fact.to' => 'nullable|numeric',
            'cb_payment_diff' => 'nullable|bool',
            'cb_payment_late' => 'nullable|bool',
            'cb_include_finished' => 'nullable|bool',
        ]);

        $service = new ReportPaymentFilterService($request->_token);
        $rules_count = $service->setFilter($data);

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);
    }


    public function filterRemove(ListFilterRequest $request)
    {
        $service = new ReportPaymentFilterService($request->_token);
        $service->clearFilter();

        return response()->json([
            'result' => 'success'
        ]);
    }
}
