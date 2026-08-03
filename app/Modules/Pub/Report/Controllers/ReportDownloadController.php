<?php

namespace App\Modules\Pub\Report\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Currency\Repository\CurrencyRepository;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Repositories\NeuroserviceRepository;
use App\Modules\Pub\Payment\Services\PaymentService;
use App\Modules\Pub\Proposal\Controllers\ProposalUpdateRequest;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Report\Services\ChinaReportService;
use App\Modules\Pub\Report\Services\ReportDownloadService;
use App\Modules\Pub\Report\Services\ReportLicenseKeysFilterService;
use App\Modules\Pub\Report\Services\ReportPaymentFilterService;
use App\Modules\Pub\Report\Services\ReportService;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\User\Models\User;
use App\Http\Controllers\Controller;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class ReportDownloadController extends Controller
{
    public function china1(Request $request)
    {

        $request->validate([
            'key' => 'required|array|exists:license_keys,id',
            'currency' => 'required|exists:currencies,slug',
            'rates' => 'required|array',
        ]);

        $content = ChinaReportService::generate_first(form: $request->all());

        return response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Dis    position' => 'attachment; filename="report_china_1.xlsx"',
            'Cache-Control' => 'max-age=0',
        ]);
    }


    public function specs(string $mode = null)
    {
        $filename = ReportDownloadService::specs($mode);
        return Storage::download($filename);
    }


    public function payments(string $mode = null)
    {
        $filename = ReportDownloadService::payments($mode);
        return Storage::download($filename);
    }

    public function tbl_industry_name(string $mode = null)
    {
        $filename = ReportDownloadService::tbl_industry_name($mode);
        return Storage::download($filename);
    }

    public function tbl_country_status__quarter(string $mode = null)
    {
        $filename = ReportDownloadService::tbl_country_status__quarter($mode);
        return Storage::download($filename);
    }

    public function tbl_manager_status__quarter(string $mode = null)
    {
        $filename = ReportDownloadService::tbl_manager_status__quarter($mode);
        return Storage::download($filename);
    }


    public function tbl_country_status__month(string $mode = null)
    {
        $filename = ReportDownloadService::tbl_country_status__month($mode);
        return Storage::download($filename);
    }

    public function tbl_status_country__month(string $mode = null)
    {
        $filename = ReportDownloadService::tbl_status_country__month($mode);
        return Storage::download($filename);
    }

}
