<?php

namespace App\Modules\Pub\Log\Controllers;

use App\Modules\Pub\Breadcrumbs\Traits\HasBreadcrumb;
use App\Modules\Pub\Company\Repositories\CompanyRepository;
use App\Modules\Pub\Log\Models\Log;
use App\Modules\Pub\Log\Repositories\LogRepository;
use App\Modules\Pub\Neuroservice\Models\Neuroservice;
use App\Modules\Pub\Neuroservice\Repositories\NeuroserviceRepository;
use App\Modules\Pub\Proposal\Controllers\ProposalUpdateRequest;
use App\Modules\Pub\Proposal\Models\Proposal;
use App\Modules\Pub\Proposal\Repositories\ProposalRepository;
use App\Modules\Pub\Proposal\Services\ProposalListFilterService;
use App\Modules\Pub\Proposal\Services\ProposalService;
use App\Modules\Pub\Partner\Models\Partner;
use App\Modules\Pub\Partner\Repositories\PartnerRepository;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\User\Models\User;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;

class LogController extends Controller
{
    use HasBreadcrumb;

    public $repo;
    private $service;

    public function __construct()
    {
        $this->repo = new ProposalRepository();
        $this->service = new ProposalService();
        $this->breadcrumb_add(route('proposal.index'), 'Журналирование');
    }

    /**
     * Страница со списком
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function index($period = null)
    {
        $pointer = Session::get('log_date');
        if (!empty($period)) {
            $pointer = Carbon::createFromFormat('d.m.Y', $period);
            Session::put('log_date', $pointer);
        }
        if (empty($pointer)) $pointer = now();

        $companies = CompanyRepository::getAll();
//        $proposals = ProposalRepository::getForCompanies();
        $proposals = ProposalRepository::getAll();

        $days = LogRepository::getDays();




        return view('pub.log.index', [
            'pointer' => $pointer,
            'companies' => $companies,
            'proposals' => $proposals,
            'breadcrumbs' => $this->breadcrumb,
            'days' => $days,
        ]);
    }

    public function day($period = null)
    {
        $period = Carbon::createFromTimestamp(strtotime($period));
        $logs = LogRepository::getForDay($period);

        return view('pub.log.day', [
            'pointer' => $period,
            'logs' => $logs,
            'breadcrumbs' => $this->breadcrumb,
        ]);
    }
    public function all()
    {
        $logs = LogRepository::getAll();
        $arCompaniesId = $logs->flatMap->pluck('company_id')->unique();
        if($arCompaniesId->isNotEmpty()) {
            $companies = CompanyRepository::getByID($arCompaniesId->toArray());
        } else {
            $companies = collect();
        }

        return view('pub.log.all', [
            'logs' => $logs,
            'breadcrumbs' => $this->breadcrumb,
            'companies' => $companies,
        ]);
    }

    public function box_detail(Log $log)
    {
        $template = View::make('pub.log.boxes.detail', [
            'log' => $log,

        ]);

        return $template;
    }

    public function box_fast(Proposal $proposal)
    {
        $group = $proposal->group;

        $template = View::make('pub.log.boxes.fast', [
            'group' => $group,

        ]);

        return $template;
    }
}
