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
use App\Modules\Pub\Report\Services\ReportLicenseKeysFilterService;
use App\Modules\Pub\Report\Services\ReportPaymentFilterService;
use App\Modules\Pub\Report\Services\ReportService;
use App\Modules\Pub\Report\Services\ReportSpecService;
use App\Modules\Pub\Scenario\Repository\ScenarioRepository;
use App\Modules\Pub\Sector\Repositories\SectorRepository;
use App\Modules\Pub\Software\Repositories\SoftwareRepository;
use App\Modules\Pub\User\Models\User;
use App\Http\Controllers\Controller;
use App\Modules\Pub\User\Repositories\UserRepository;
use App\Modules\Pub\Work\Repositories\WorkRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;

class ReportController extends Controller
{
    use HasBreadcrumb;

    public function popular_scenario()
    {
        $this->breadcrumb_add('', 'Отчёты');
        $this->breadcrumb_add('', 'Популярые сценарии и сервисы');

        $data = ReportService::scenario_popular();
        $scenarios = ScenarioRepository::getActive();
        $services = NeuroserviceRepository::getAll();

        return view('pub.report.popular', [
            'breadcrumbs' => $this->breadcrumb,
            'title' => 'Популярность сценариев и сервисов',
            'scenarios' => $scenarios,
            'services' => $services,
            'data' => $data,
        ]);
    }

    public function payments()
    {
        $this->breadcrumb_add('', 'Отчёты');
        $this->breadcrumb_add('', 'Оплаты');

        $filter_service = new ReportPaymentFilterService();
        $filter = $filter_service->getFilter();

        $currency_slug = Cache::get('dashboard_currency') ?? "RUB";
        $currency = CurrencyRepository::get($currency_slug);

        $data = ReportService::getPaymentSummary($currency, $filter);

        return view('pub.report.payment',  [
            'breadcrumbs' => $this->breadcrumb,
            'companies' => CompanyRepository::getAll(),
            'users' => UserRepository::getAllWithTrashed(),
            'filter' => $filter,
            'data' => $data,
            'currency' => $currency,
        ]);
    }

    public function specs()
    {
        $this->breadcrumb_add('', 'Отчёты');
        $this->breadcrumb_add('', 'Конфигурации. Сводная');


        $mode = !empty($_GET['mode']) && $_GET['mode'] == 'all' ? 'all' : 'filtered';
        $data = ReportSpecService::specs($mode);



        return view('pub.report.specs',  [
            'breadcrumbs' => $this->breadcrumb,
            'companies' => CompanyRepository::getAll(),
            'data' => $data,
            'mode' => $mode,
        ]);
    }

    public function scenarios_specs(): \Illuminate\View\View
    {
        $this->breadcrumb_add('', 'Отчёты');
        $this->breadcrumb_add('', 'Сценарии по спецификациям');


        $filter_service = new ReportPaymentFilterService();
        $filter = $filter_service->getFilter();
        $data = ReportService::getScenariosSpecsSummary($filter);

        return view('pub.report.scenarios_specs', [
            'breadcrumbs' => $this->breadcrumb,
            'companies' => CompanyRepository::getAll(),
            'filter' => $filter,
            'data' => $data,
        ]);
    }

    public function scenarios(): \Illuminate\View\View
    {
        $this->breadcrumb_add('', 'Отчёты');
        $this->breadcrumb_add('', 'Список сценариев');


        $data = ReportService::getScenarios();
        return view('pub.report.scenarios', [
            'breadcrumbs' => $this->breadcrumb,
            'data' => $data,
        ]);
    }

    public function license_keys(): \Illuminate\View\View
    {
        $this->breadcrumb_add('', 'Отчёты');
        $this->breadcrumb_add('', 'Лицензионные ключи');


        $filter_service = new ReportLicenseKeysFilterService();
        $filter = $filter_service->getFilter();

        $data = ReportService::getLicenseKeysSummary($filter);

        return view('pub.report.license_keys', [
            'breadcrumbs' => $this->breadcrumb,
            'companies' => CompanyRepository::getAll(),
            'filter' => $filter,
            'data' => $data,
        ]);
    }

    public function china(): \Illuminate\View\View
    {
        $this->breadcrumb_add('', 'Отчёты (Китай)');
        $data_report_1 = ChinaReportService::getData__China1();
        $currency_date = now()->startOfWeek();


        return view('pub.report.china', [
            'breadcrumbs' => $this->breadcrumb,
            'data_report_1' => $data_report_1,
            'rates' => CurrencyRepository::getRates(['date' => $currency_date, 'returnFull' => true]),
            'currencies' => CurrencyRepository::getForeign(),
            'currency_date' => $currency_date,
        ]);
    }



}
