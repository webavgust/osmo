<?php

namespace App\Modules\Pub\Report\Controllers\Api;

use App\Modules\Pub\Proposal\Requests\ListFilterRequest;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Report\Services\ReportLicenseKeysFilterService;
use App\Modules\Pub\Report\Services\ReportPaymentFilterService;
use App\Modules\Pub\Report\Services\ReportSpecService;
use Illuminate\Http\Request;

class ApiReportSpecsController
{
    public function active(Request $request)
    {
        $request->validate(['active' => 'nullable|array']);

        ReportSpecService::setActive($request->active);

        return ['result' => 'success', 'url' => route('report.specs')];
    }
}
