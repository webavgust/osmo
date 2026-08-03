<?php

namespace App\Modules\Pub\Report\Controllers\Api;

use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Report\Services\ReportLicenseKeysFilterService;
use App\Modules\Pub\Report\Services\ReportPaymentFilterService;
use Illuminate\Http\Request;

class ApiReportLicenseKeysController
{
    public function filter(Request $request)
    {
        $data = $request->validate([
            'company' => 'nullable|exists:companies,id',
            'contract_number' => 'nullable|string',
            'active_from' => 'nullable|string',
            'active_to' => 'nullable|string',
            'cb_show_unactive' => 'nullable|bool',
            'cb_expired_3' => 'nullable|bool',
        ]);

        $service = new ReportLicenseKeysFilterService($request->_token);
        $rules_count = $service->setFilter($data);

        return response()->json([
            'result' => 'success',
            'rules_count' => $rules_count
        ]);
    }


    public function filterRemove(ListFilterRequest $request)
    {
        $service = new ReportLicenseKeysFilterService($request->_token);
        $service->clearFilter();

        return response()->json([
            'result' => 'success'
        ]);
    }
}
